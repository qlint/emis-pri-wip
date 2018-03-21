<?php
$app->post('/parentLogin', function () use($app) {
  // Log parent in
  $allPostVars = $app->request->post();
  $username = $allPostVars['user_name'];
  $pwd = $allPostVars['user_pwd'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();
    $sth = $db->prepare("SELECT parents.parent_id, username, active, first_name, middle_name, last_name, email,
                  first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name, device_user_id
                FROM parents
                INNER JOIN parent_students ON parents.parent_id = parent_students.parent_id
                WHERE username= :username
                AND password = :password
                AND active is true");
    $sth->execute( array(':username' => $username, ':password' => $pwd) );
    $result = $sth->fetch(PDO::FETCH_OBJ);

    if($result) {

      // get the parents' students and add to result
      $sth1 = $db->prepare("SELECT student_id, subdomain, dbusername, dbpassword
                            FROM parent_students
                            WHERE parent_id = :parentId
                            ORDER BY subdomain");
      $sth1->execute(array(':parentId' => $result->parent_id));
      $students = $sth1->fetchAll(PDO::FETCH_OBJ);
      $db = null;

      $studentDetails = Array();
      $curSubDomain = '';
      $studentsBySchool = Array();
      foreach( $students as $student )
      {
        // get individual student details
        // only get new db connection if different subdomain
        if( $curSubDomain != $student->subdomain )
        {
          if( $db !== null ) $db = null;
          $db = setDBConnection($student->subdomain);
        }
        $sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image, admission_number,
                       first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                       students.active, class_name, class_id, class_cat_id, report_card_type,
                       (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
                    FROM app.students
                    INNER JOIN app.classes ON students.current_class = classes.class_id
                    WHERE student_id = :studentId");
        $sth3->execute(array(':studentId' => $student->student_id));
        $details = $sth3->fetch(PDO::FETCH_OBJ);

        if( $details ) {
          $details->school = $student->subdomain;

          if( $details->student_id !== null )
          {
            $studentDetails[] = $details;
            $curSubDomain = $student->subdomain;

            /* build an array for grabbing news */
            $studentsBySchool[$curSubDomain][] = $student->student_id;
          }
        }
      }

      /* get news for students, only want new once per school, and student specific news */
      $news = Array();
      foreach( $studentsBySchool as $school => $students )
      {
        if( $db !== null ) $db = null;
        $db = setDBConnection($school);

        $sth5 = $db->prepare("SELECT
                    com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                    employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                    audience, attachment, reply_to,
                    students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                    guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                    communications.guardian_id, communications.student_id, classes.class_name, post_status,
                    sent, sent_date, message_from,
                    case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                  FROM app.communications
                  LEFT JOIN app.students ON communications.student_id = students.student_id
                  LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                  LEFT JOIN app.classes ON communications.class_id = classes.class_id
                  INNER JOIN app.employees ON communications.message_from = employees.emp_id
                  INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                  INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                  INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                  WHERE communications.student_id = any(:studentIds) OR communications.student_id is null
                  AND communications.post_status_id = 1
                  AND (communications.class_id = any(select current_class from app.students where student_id = any(:studentIds)) OR communications.class_id is null)
                  ORDER BY creation_date desc");

        $studentsArray = "{" . implode(',',$students) . "}";
        $sth5->execute(array(':studentIds' => $studentsArray));
        $news[$school] = $sth5->fetchAll(PDO::FETCH_OBJ);

      }

      $result->students = $studentDetails;
      $result->news = $news;

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      $db = null;

      echo json_encode(array('response' => 'success', 'data' => $result ));

    } else {
      throw new PDOException('The username or password you have entered is incorrect.');
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(401);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->put('/updatePassword', function () use($app) {
  // update password
  $allPostVars = json_decode($app->request()->getBody(),true);
  $userId = $allPostVars['parent_id'];
  $oldPwd = $allPostVars['user_pwd'];
  $newPwd = $allPostVars['new_password'];

  //$hash = password_hash($pwd, PASSWORD_BCRYPT);

  try
  {
    $db = getLoginDB();

    $sth1 = $db->prepare("SELECT * FROM parents WHERE parent_id = :userId and password = :oldPwd");
    $sth1->execute( array(':userId' => $userId, ':oldPwd' => $oldPwd) );
    $result = $sth1->fetch(PDO::FETCH_OBJ);
    if( $result )
    {
      $sth2 = $db->prepare("UPDATE parents SET password = :newPwd WHERE parent_id = :userId");
      $sth2->execute( array(':userId' => $userId, ':newPwd' => $newPwd) );
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1));
    }
    else
    {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "error", "data" => "Current password is incorrect."));
    }


    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->post('/updateDeviceUserId', function () use($app) {
  // update users device id
  $allPostVars = $app->request->post();
  $parentId = $allPostVars['parent_id'];
  $deviceUserId = $allPostVars['device_user_id'];

  try
  {
    $db = getLoginDB();

    $sth = $db->prepare("UPDATE parents SET device_user_id = :deviceUserId WHERE parent_id = :parentId");
    $sth->execute( array(':parentId' => $parentId, ':deviceUserId' => $deviceUserId) );

    $db = null;

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getParentStudents/:parent_id', function ($parentId){
  // get parents students and news
  $app = \Slim\Slim::getInstance();
  try
  {
    $db = getLoginDB();

    // get the parents' students and add to result
    $sth1 = $db->prepare("SELECT student_id, subdomain, dbusername, dbpassword FROM parent_students WHERE parent_id = :parentId ORDER BY subdomain");
    $sth1->execute(array(':parentId' => $parentId));
    $students = $sth1->fetchAll(PDO::FETCH_OBJ);
    $db = null;

    $studentDetails = Array();
    $curSubDomain = '';
    $studentsBySchool = Array();
    foreach( $students as $student )
    {
      // get individual student details
      // only get new db connection if different subdomain
      if( $curSubDomain != $student->subdomain )
      {
        if( $db !== null ) $db = null;
        $db = setDBConnection($student->subdomain);
      }
      $sth3 = $db->prepare("SELECT student_id, first_name, middle_name, last_name, student_image, admission_number,
                     first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                     students.active, class_name, class_id, class_cat_id, report_card_type,
                     (SELECT value FROM app.settings WHERE name = 'School Name') as school_name
                  FROM app.students
                  INNER JOIN app.classes ON students.current_class = classes.class_id
                  WHERE student_id = :studentId");
      $sth3->execute(array(':studentId' => $student->student_id));
      $details = $sth3->fetch(PDO::FETCH_OBJ);
      if($details){
        $details->school = $student->subdomain;

        if( $details->student_id !== null )
        {
          $studentDetails[] = $details;
          $curSubDomain = $student->subdomain;

          /* build an array for grabbing news */
          $studentsBySchool[$curSubDomain][] = $student->student_id;
        }
      }
    }

    /* get news for students, only want new once per school, and student specific news */
    $news = Array();
    foreach( $studentsBySchool as $school => $students )
    {
      if( $db !== null ) $db = null;
      $db = setDBConnection($school);

      $sth5 = $db->prepare("SELECT
                  com_id, com_date, communications.creation_date, com_type, subject, message, send_as_email, send_as_sms,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  audience, attachment, reply_to,
                  students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name as student_name,
                  guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name as parent_full_name,
                  communications.guardian_id, communications.student_id, classes.class_name, post_status,
                  sent, sent_date, message_from,
                  case when send_as_email is true then 'email' when send_as_sms is true then 'sms' end as send_method
                FROM app.communications
                LEFT JOIN app.students ON communications.student_id = students.student_id
                LEFT JOIN app.guardians ON communications.guardian_id = guardians.guardian_id
                LEFT JOIN app.classes ON communications.class_id = classes.class_id
                INNER JOIN app.employees ON communications.message_from = employees.emp_id
                INNER JOIN app.communication_types ON communications.com_type_id = communication_types.com_type_id
                INNER JOIN app.communication_audience ON communications.audience_id = communication_audience.audience_id
                INNER JOIN app.blog_post_statuses ON communications.post_status_id = blog_post_statuses.post_status_id
                WHERE (communications.student_id = any(:studentIds) OR communications.student_id is null)
                AND communications.post_status_id = 1
                AND (communications.class_id = any(select current_class from app.students where student_id = any(:studentIds)) OR communications.class_id is null)
                ORDER BY creation_date desc");

      $studentsArray = "{" . implode(',',$students) . "}";
      $sth5->execute(array(':studentIds' => $studentsArray));
      $news[$school] = $sth5->fetchAll(PDO::FETCH_OBJ);

    }

    $result = new stdClass();
    $result->students = $studentDetails;
    $result->news = $news;
    $result->notices = Array();

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    $db = null;

    echo json_encode(array('response' => 'success', 'data' => $result ));



  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->get('/getSchoolCurrency/:school', function ($school) {
    //Show currency

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT value FROM app.settings WHERE name = 'Currency'");
    $sth->execute();
    $settings = $sth->fetch(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getSchoolContactInfo/:school', function ($school) {
    //Show contact info

  $app = \Slim\Slim::getInstance();

    try
    {
      $db = setDBConnection($school);
      $sth = $db->prepare("SELECT name, value FROM app.settings");
      $sth->execute();
      $settings = $sth->fetchAll(PDO::FETCH_OBJ);

        if($settings) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $settings ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getBlog/:school/:student_id(/:pageNumber)', function ($school, $studentId, $pageNumber=null) {
    // Get published blog posts associated with student for this current school year

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    if( $pageNumber === null )
    {
      $sth = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  feature_image, blog_posts.modified_date,
                  class_name, blogs.class_id, blogs.blog_name
               FROM app.blogs
                INNER JOIN app.blog_posts
                  INNER JOIN app.employees
                  ON blog_posts.created_by = employees.emp_id
                  LEFT JOIN app.blog_post_types
                  ON blog_posts.post_type_id = blog_post_types.post_type_id
                  INNER JOIN app.blog_post_statuses
                  ON blog_posts.post_status_id = blog_post_statuses.post_status_id
                ON blogs.blog_id = blog_posts.blog_id
                INNER JOIN app.classes
                ON blogs.class_id = classes.class_id
                INNER JOIN app.students ON blogs.class_id = students.current_class
                WHERE student_id = :studentId
                AND blog_posts.post_status_id = 1
               -- AND blog_posts.post_type_id = 1
                AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
                ORDER BY blog_posts.creation_date desc
                ");
      $sth->execute( array(':studentId' => $studentId) );
      $results = $sth->fetchAll(PDO::FETCH_OBJ);
    }
    else
    {
      // need pagination
      $limit = 3;
      $offset = ($pageNumber * $limit) - $limit;
      $sth1 = $db->prepare("SELECT count(post_id) as num_posts
               FROM app.blogs
               INNER JOIN app.blog_posts ON blogs.blog_id = blog_posts.blog_id
               INNER JOIN app.classes ON blogs.class_id = classes.class_id
               INNER JOIN app.students ON blogs.class_id = students.current_class
               WHERE student_id = :studentId
               AND blog_posts.post_status_id = 1
              -- AND blog_posts.post_type_id = 1
               AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
                ");

      $sth2 = $db->prepare("SELECT post_id, blogs.blog_id, blog_posts.creation_date, title, post_type, body, blog_posts.post_status_id, post_status,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  feature_image, blog_posts.modified_date,
                  class_name, blogs.class_id, blogs.blog_name
               FROM app.blogs
                INNER JOIN app.blog_posts
                  INNER JOIN app.employees
                  ON blog_posts.created_by = employees.emp_id
                  LEFT JOIN app.blog_post_types
                  ON blog_posts.post_type_id = blog_post_types.post_type_id
                  INNER JOIN app.blog_post_statuses
                  ON blog_posts.post_status_id = blog_post_statuses.post_status_id
                ON blogs.blog_id = blog_posts.blog_id
                INNER JOIN app.classes
                ON blogs.class_id = classes.class_id
                INNER JOIN app.students ON blogs.class_id = students.current_class
                WHERE student_id = :studentId
                AND blog_posts.post_status_id = 1
                --AND blog_posts.post_type_id = 1
                AND date_trunc('year', blog_posts.creation_date) =  date_trunc('year', now())
                ORDER BY blog_posts.creation_date desc
                OFFSET :offset LIMIT :limit
                ");

      $db->beginTransaction();
      $sth1->execute( array(':studentId' => $studentId) );
      $sth2->execute( array(':studentId' => $studentId, ':offset' => $offset, ':limit' => $limit) );
      $db->commit();

      $count = $sth1->fetch(PDO::FETCH_OBJ);
      $posts = $sth2->fetchAll(PDO::FETCH_OBJ);

      $pagination = new stdClass();
      $pagination->page = $pageNumber;
      $pagination->perPage = $limit;
      $pagination->pageCount = floor($count->num_posts / $limit) + 1;
      $pagination->totalCount = (int) $count->num_posts;

      $results = new stdClass();
      $results->count = $count->num_posts;
      $results->pagination = $pagination;
      $results->posts = $posts;
    }

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getHomework/:school/:student_id', function ($school, $studentId) {
    // Get homework associated with student for current week

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    $sth = $db->prepare("SELECT homework_id, assigned_date, title, body, homework.post_status_id, post_status,
                  employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as posted_by,
                  attachment, homework.modified_date,
                  class_name, class_subjects.class_id, due_date, subject_name
                FROM app.homework
                INNER JOIN app.employees
                ON homework.created_by = employees.emp_id
                INNER JOIN app.blog_post_statuses
                ON homework.post_status_id = blog_post_statuses.post_status_id
                INNER JOIN app.class_subjects
                  INNER JOIN app.classes
                    INNER JOIN app.students
                    ON classes.class_id = students.current_class
                  ON class_subjects.class_id = classes.class_id
                  INNER JOIN app.subjects
                  ON class_subjects.subject_id = subjects.subject_id
                ON homework.class_subject_id = class_subjects.class_subject_id
                WHERE student_id = :studentId
                AND homework.post_status_id = 1
                --AND date_trunc('year', homework.creation_date) =  date_trunc('year', now())
                AND (assigned_date between date_trunc('week', now())::date and (date_trunc('week', now())+ '6 days'::interval)::date
                  OR due_date > now() )
                ORDER BY homework.assigned_date, subjects.sort_order
                ");
    $sth->execute( array(':studentId' => $studentId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);
    /*
        $sth = $db->prepare("SELECT homework_date, description
                FROM app.homework
                INNER JOIN app.students ON homework.class_id = students.current_class
                WHERE student_id = :studentId
                AND homework_date between date_trunc('week', now())::date and (date_trunc('week', now())+ '6 days'::interval)::date
                ORDER BY homework_date asc
                ");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
    */

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudent/:school/:studentId', function ($school, $studentId) {
    //Show all students

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT students.student_id, first_name, middle_name, last_name, admission_number, admission_date,
                  student_category, gender, dob, student_image, classes.class_name,
                payment_plan_name,
                emergency_name, emergency_relationship, emergency_telephone, pick_up_drop_off_individual,
                other_medical_conditions, other_medical_conditions_description,
                medical_conditions, hospitalized, current_medical_treatment, hospitalized_description,
                current_medical_treatment_description, comments
               FROM app.students
               INNER JOIN app.classes ON students.current_class = classes.class_id
               LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
               WHERE student_id = :studentID
               ORDER BY first_name, middle_name, last_name");
        $sth->execute( array(':studentID' => $studentId));
        $results = $sth->fetch(PDO::FETCH_OBJ);

        if($results) {

      // get parents
      $sth2 = $db->prepare("SELECT *
               FROM app.student_guardians
               INNER JOIN app.guardians ON student_guardians.guardian_id = guardians.guardian_id
               WHERE student_guardians.student_id = :studentID
               AND student_guardians.active = true
               ORDER BY relationship, last_name, first_name, middle_name");
      $sth2->execute( array(':studentID' => $studentId));
      $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);

      $results->guardians = $results2;

      // get medical history
      $sth3 = $db->prepare("SELECT medical_id, illness_condition, age, comments, creation_date as date_medical_added
               FROM app.student_medical_history
               WHERE student_id = :studentID
               ORDER BY creation_date");
      $sth3->execute( array(':studentID' => $studentId));
      $results3 = $sth3->fetchAll(PDO::FETCH_OBJ);

      $results->medical_history = $results3;

      // get fee items
      $sth4 = $db->prepare("SELECT
                  fee_item
                FROM app.student_fee_items
                INNER JOIN app.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
                WHERE student_id = :studentID
                AND optional is true
                ORDER BY student_fee_items.creation_date");
      $sth4->execute( array(':studentID' => $studentId));
      $results4 = $sth4->fetchAll(PDO::FETCH_OBJ);

      $results->fee_items = $results4;

            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentBalancePortal/:school/:studentId', function ($school, $studentId) {
  // Return students fee summary

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get total amount of student fee items
    // calculate the amount due and due date
    // calculate the balance owing
    $sth = $db->prepare("SELECT fee_item, q.payment_method,
                          sum(invoice_total) AS total_due,
                          sum(total_paid) AS total_paid,
                          sum(total_paid) - sum(invoice_total) AS balance,
                          (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                        FROM
                          ( SELECT invoice_line_items.amount as invoice_total,
                             fee_item,
                             student_fee_items.payment_method,
                             inv_item_id,
                             (SELECT COALESCE(sum(payment_inv_items.amount), 0)
                              FROM app.payment_inv_items
                              INNER JOIN app.payments
                              ON payment_inv_items.payment_id = payments.payment_id AND reversed is false
                              WHERE inv_item_id = invoice_line_items.inv_item_id) as total_paid
                            FROM app.invoices
                            INNER JOIN app.invoice_line_items
                              INNER JOIN app.student_fee_items
                                INNER JOIN app.fee_items
                                ON student_fee_items.fee_item_id = fee_items.fee_item_id
                              ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
                            ON invoices.inv_id = invoice_line_items.inv_id
                            WHERE invoices.student_id = :studentID
                            AND invoices.canceled = false
                          ) q
                        GROUP BY fee_item, q.payment_method
              ");
        $sth->execute( array(':studentID' => $studentId));
        $fees = $sth->fetchAll(PDO::FETCH_OBJ);


    if( $fees )
    {
      $sth2 = $db->prepare("SELECT
                  (SELECT due_date FROM app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_due_date,
                  (SELECT balance from app.invoice_balances2 WHERE student_id = :studentID AND due_date > now()::date AND canceled = false order by due_date asc limit 1) AS next_amount,
                  COALESCE((SELECT sum(amount) from app.credits WHERE student_id = :studentID ),0) AS total_credit,
                  (SELECT sum(balance) from app.invoice_balances2 WHERE student_id = :studentID AND due_date <= now()::date AND canceled = false) AS arrears,
                  (SELECT value FROM app.settings WHERE name = 'Currency') as currency");
      $sth2->execute( array(':studentID' => $studentId));
      $details = $sth2->fetch(PDO::FETCH_OBJ);


      if( $details )
      {
        //  set the next due summary
        $feeSummary = new Stdclass();
        $feeSummary->next_due_date = $details->next_due_date;
        $feeSummary->next_amount = $details->next_amount;
        //$feeSummary->unapplied_payments = $details->unapplied_payments;
        $feeSummary->total_credit = $details->total_credit;
        $feeSummary->arrears = $details->arrears;

        // is the next due date within 30 days?
        $diff = dateDiff("now", $details->next_due_date);
        $feeSummary->within30days = ( $diff < 30 ? true : false );
      }

      $balanceQry = $db->prepare("SELECT total_due, total_paid, total_paid - total_due as balance,
                                  case when (select count(*) from app.invoice_balances2 where student_id = :studentID and past_due is true) > 0 then true else false end as past_due
                                FROM (
                                  SELECT
                                    coalesce(sum(total_amount),0) as total_due,
                                    coalesce((select sum(payment_inv_items.amount) from app.payments inner join app.payment_inv_items on payments.payment_id = payment_inv_items.payment_id where student_id = :studentID),0) as total_paid
                                  FROM app.invoices
                                  WHERE student_id = :studentID
                                  --AND date_part('year', due_date) = date_part('year',now())
                                  AND canceled = false
                                )q");
      $balanceQry->execute( array(':studentID' => $studentId));
      $balance = $balanceQry->fetch(PDO::FETCH_OBJ);
      //var_dump($balance);

      $feeSummary->total_due = ($balance ? $balance->total_due : 0);
      $feeSummary->total_paid = ($balance ? $balance->total_paid : 0);
      $feeSummary->balance = ($balance ? $balance->balance : 0);
      $feeSummary->past_due = ($balance ? $balance->past_due : false);

      $results = new stdClass();
      $results->fee_summary = $feeSummary;
      $results->fees = $fees;

    }

        if($fees) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentInvoicesPortal/:school/:studentId', function ($school, $studentId) {
  // Return students invoices

  $app = \Slim\Slim::getInstance();

  try
  {
     $db = setDBConnection($school);

    // get invoices
    // TO DO: I only want invoices for this school year?
    $sth = $db->prepare("SELECT invoice_balances2.*, ARRAY(select fee_item || ' (' || invoice_line_items.amount || ')'
                                    from app.invoice_line_items
                                    inner join app.student_fee_items
                                    inner join app.fee_items
                                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                                    on invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                                    where inv_id = invoice_balances2.inv_id) as invoice_items,
                                    term_name,
                                    date_part('year', terms.start_date) as year,
                                    (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                          FROM app.invoice_balances2
                          INNER JOIN app.terms
                          ON invoice_balances2.term_id = terms.term_id
                          WHERE student_id = :studentId
                          ORDER BY inv_date");
    $sth->execute( array(':studentId' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);


  if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
  } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
  }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentPaymentsPortal/:school/:studentId', function ($school, $studentId) {
  // Return students payments

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    // get payments
    // TO DO: I only want payments for this school year?
    $sth = $db->prepare("SELECT payments.payment_id,
                payment_date,
                payment_method,
                amount,
                reversed,
                reversed_date,
                replacement_payment,
                slip_cheque_no,
                COALESCE(
                CASE WHEN replacement_payment = true THEN
                 (SELECT string_agg(fee_item || ' Replacement', ',')
                     FROM app.payment_replacement_items
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     )
                 ELSE
                  (SELECT
                    string_agg(item, '<br>')
                  FROM (
                    select 'Inv #' || payment_inv_items.inv_id || ' (' || string_agg(fee_item, ', ' order by fee_item) || ')' as item
                     FROM app.payment_inv_items
                     INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
                     INNER JOIN app.invoice_line_items using (inv_item_id)
                     INNER JOIN app.student_fee_items using (student_fee_item_id)
                     INNER JOIN app.fee_items using (fee_item_id)
                     WHERE payment_id = payments.payment_id
                     group by payment_inv_items.inv_id
                  ) q
                     )
                END, 'Credit') as applied_to,
               COALESCE((
                  amount - coalesce((select coalesce(sum(amount),0)  as sum
                            from app.payment_inv_items
                            inner join app.invoices using (inv_id)
                            where payment_id = payments.payment_id
                            and canceled = false ),0)
                ),0) AS unapplied_amount,
                (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                FROM app.payments
                WHERE student_id = :studentID
                GROUP BY payments.payment_id");
    $sth->execute( array(':studentID' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);


    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentCreditsPortal/:school/:studentId', function ($school, $studentId) {
  // Return students credits

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get credits
    $sth = $db->prepare("SELECT credit_id, credits.amount, payment_date, credits.payment_id, payment_method, slip_cheque_no,
                        (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                FROM app.credits
                INNER JOIN app.payments ON credits.payment_id = payments.payment_id
                WHERE credits.student_id = :studentID
                AND reversed is false
                ORDER BY payment_date");
    $sth->execute( array(':studentID' => $studentId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentArrearsPortal/:school/:studentId/:date', function ($school, $studentId, $date) {
  // Return students arrears

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = setDBConnection($school);

    // get credits
    $sth = $db->prepare("select sum(total_paid - total_amount) as balance, (SELECT value FROM app.settings WHERE name = 'Currency') as currency
                          from (
                            select invoices.inv_id, invoices.total_amount, coalesce(sum(amount),0) as total_paid
                            from app.invoices
                            left join app.payment_inv_items
                            on invoices.inv_id = payment_inv_items.inv_id
                            WHERE student_id = :studentID
                            AND canceled = false
                            AND due_date <= :date
                            group by invoices.inv_id
                          ) q");
    $sth->execute( array(':studentID' => $studentId, ':date' => $date));
    $results = $sth->fetch(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentFeeItemsPortal/:school/:studentId', function ($school, $studentId) {
    // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

    try
    {
       $db = setDBConnection($school);
    // TO DO: I only want fee items for this school year?
       $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount, frequency,
                            (SELECT value FROM app.settings WHERE name = 'Currency') as currency
              FROM app.student_fee_items
              INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true
              WHERE student_id = :studentId
              AND student_fee_items.active = true
              ORDER BY fee_item");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentExamMarksPortal/:school/:student_id/:class/:term', function ($school,$studentId,$classId,$termId) {
    //Get student exam marks

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    // get exam marks by exam type
    $sth = $db->prepare("SELECT subject_name, (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
                ,exam_type
                ,mark
                ,grade_weight
                ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
            FROM app.exam_marks
            INNER JOIN app.class_subject_exams
            INNER JOIN app.exam_types
            ON class_subject_exams.exam_type_id = exam_types.exam_type_id
            INNER JOIN app.class_subjects
              INNER JOIN app.subjects
              ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
            ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
            ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
            WHERE class_subjects.class_id = :classId
            AND term_id = :termId
            AND student_id = :studentId
            ORDER BY subjects.sort_order, exam_types.exam_type_id
            ");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );

        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentClasses/:school/:studentId', function ($school, $studentId) {
    // Get all students classes, present and past

  $app = \Slim\Slim::getInstance();

    try
    {
       $db = setDBConnection($school);
       $sth = $db->prepare("SELECT 1 as ord, student_id, class_id, class_name,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 1') then true else false end as term_1,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 2') then true else false end as term_2,
                case when now() > (select start_date from app.terms where date_trunc('year', start_date) = date_trunc('year', now()) and term_name = 'Term 3') then true else false end as term_3
              FROM app.students
              INNER JOIN app.classes ON students.current_class = classes.class_id
              WHERE student_id = :studentId
              UNION
              SELECT class_history_id as ord, student_id, student_class_history.class_id, class_name, true, true, true
              FROM app.student_class_history
              INNER JOIN app.classes ON student_class_history.class_id = classes.class_id
              WHERE student_id = :studentId
              AND student_class_history.class_id != (select current_class from app.students where student_id = :studentId)
              ORDER BY ord");
    $sth->execute( array(':studentId' => $studentId) );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getCurrentTerm/:school', function ($school) {
    //Get current term

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $query = $db->prepare("SELECT term_id, term_name, start_date, end_date, date_part('year', start_date) as year FROM app.current_term");
    $query->execute();
        $results = $query->fetch(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getTerms/:school(/:year)', function ($school, $year = null) {
    //Show all terms for given year (or this year if null)

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);
    if( $year == null )
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name, start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term, date_part('year',start_date) as year
                    FROM app.terms
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute();
    }
    else
    {
      $query = $db->prepare("SELECT term_id, term_name, term_name || ' ' || date_part('year',start_date) as term_year_name,start_date, end_date,
                      case when term_id = (select term_id from app.current_term) then true else false end as current_term,
                      date_part('year',start_date) as year
                    FROM app.terms
                    WHERE date_part('year',start_date) = :year
                    ORDER BY date_part('year',start_date), term_name");
      $query->execute(array(':year' => $year));
    }

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getStudentReportCards/:school/:student_id', function ($school, $studentId) {
  //Get student report cards

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT report_card_id, report_cards.student_id, report_cards.class_id, class_name, term_name, report_cards.term_id,
                  date_part('year', start_date) as year, report_data, report_cards.report_card_type, class_cat_id,
                  report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
                  report_cards.creation_date::date as date
          FROM app.report_cards
          INNER JOIN app.students ON report_cards.student_id = students.student_id
          INNER JOIN app.classes ON report_cards.class_id = classes.class_id
          INNER JOIN app.terms ON report_cards.term_id = terms.term_id
          LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
          WHERE report_cards.student_id = :studentId
          AND published is true
          ORDER BY report_card_id");
    $sth->execute( array(':studentId' => $studentId) );

    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getStudentReportCard/:school/:student_id/:class_id/:term_id', function ($school, $studentId, $classId, $termId) {
    //Get student report card

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    $sth = $db->prepare("SELECT report_card_id, report_cards.student_id, class_name, term_name, report_cards.term_id,
                  date_part('year', start_date) as year, report_data, report_cards.report_card_type,
                  report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
                  report_cards.creation_date::date as date
          FROM app.report_cards
          INNER JOIN app.students ON report_cards.student_id = students.student_id
          INNER JOIN app.classes ON report_cards.class_id = classes.class_id
          INNER JOIN app.terms ON report_cards.term_id = terms.term_id
          LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
          WHERE report_cards.student_id = :studentId
          AND report_cards.class_id = :classId
          AND report_cards.term_id = :termId");
    $sth->execute( array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) );
        $results = $sth->fetch(PDO::FETCH_OBJ);


        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getFeeItemsPortal/:school', function ($school) {
    //Show fee items

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);
        $sth = $db->prepare("SELECT fee_item_id, fee_item, default_amount, frequency, active, class_cats_restriction, optional, new_student_only, replaceable
              FROM app.fee_items
              WHERE active = true
              ORDER BY fee_item_id");
        $sth->execute( );
        $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
           $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getPaymentDetails/:school/:payment_id', function ($school, $paymentId) {
    // Get all payment details

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = setDBConnection($school);

    // get payment data
       $sth = $db->prepare("SELECT payments.payment_id, payment_date, payments.amount, payments.payment_method, slip_cheque_no,
                  payments.student_id, replacement_payment, reversed, reversed_date, credit_id --,payments.inv_id
              FROM app.payments
              LEFT JOIN app.credits ON payments.payment_id = credits.payment_id
              WHERE payments.payment_id = :paymentId
     ");
    $sth->execute( array(':paymentId' => $paymentId) );
        $results1 = $sth->fetch(PDO::FETCH_OBJ);

    // get what the payment was applied to
    $sth2 = $db->prepare("SELECT payment_inv_item_id, payment_inv_items.inv_item_id,
                  fee_item,
                  payment_inv_items.amount as line_item_amount, invoice_line_items.inv_id
              FROM app.payment_inv_items
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
              WHERE payment_id = :paymentId
              UNION
              SELECT payment_replace_item_id, payment_replacement_items.student_fee_item_id,
                  fee_item,
                  payment_replacement_items.amount as line_item_amount, null
              FROM app.payment_replacement_items
              INNER JOIN app.student_fee_items
                INNER JOIN app.fee_items
                ON student_fee_items.fee_item_id = fee_items.fee_item_id
              ON payment_replacement_items.student_fee_item_id = student_fee_items.student_fee_item_id
              WHERE payment_id = :paymentId
              ORDER BY inv_id
              ");
    $sth2->execute( array(':paymentId' => $paymentId) );
        $results2 = $sth2->fetchAll(PDO::FETCH_OBJ);

    // Loop through and get unique inv_ids for next query
    $invIds = array();
    foreach($results2 as $result2){
      if( !in_array( $result2->inv_id, $invIds ) ) $invIds[] = $result2->inv_id;
    }
    $invIdStr = '{' . implode(',', $invIds) . '}';

    // get the invoice details that payment was applied to

    $sth3 = $db->prepare("SELECT invoices.inv_id,
                inv_date,
                (select coalesce(sum(amount),0) - invoices.total_amount from app.payment_inv_items where inv_id = invoices.inv_id) as overall_balance,
                invoice_line_items.amount,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) as total_paid,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) - invoice_line_items.amount as balance,
                due_date,
                invoice_line_items.inv_item_id,
                fee_item,
                invoice_line_items.amount as line_item_amount,
                term_name,
                date_part('year',terms.start_date) as term_year,
                invoices.canceled
              FROM app.invoices
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON invoices.inv_id = invoice_line_items.inv_id
              INNER JOIN app.terms
              ON invoices.term_id = terms.term_id
              WHERE invoices.inv_id = any(:invIds)
              ORDER BY inv_id, due_date, fee_item");

    $sth3->execute( array(':invIds' => $invIdStr) );
    $results3 = $sth3->fetchAll(PDO::FETCH_OBJ);


    $results = new Stdclass();
    $results->payment = $results1;
    $results->paymentItems = $results2;
    $results->invoice = $results3;

    if($results) {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
    } else {
        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
        $db = null;
    }
  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getInvoiceDetails/:school/:inv_id', function ($school,$invId) {
  // Get all invoice details

  $app = \Slim\Slim::getInstance();

  try
  {
     $db = setDBConnection($school);
     $sth = $db->prepare("SELECT *, amount - total_paid as balance
              FROM (
              SELECT
                invoices.inv_id,
                inv_date,
                invoice_line_items.amount,
                coalesce((select sum(payment_inv_items.amount)
                    from app.payment_inv_items
                    inner join app.payments on payment_inv_items.payment_id = payments.payment_id
                    where payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
                    AND reversed = false),0) as total_paid,
                due_date,
                inv_item_id,
                fee_item
              FROM app.invoices
              INNER JOIN app.invoice_line_items
                INNER JOIN app.student_fee_items
                  INNER JOIN app.fee_items
                  ON student_fee_items.fee_item_id = fee_items.fee_item_id
                ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
              ON invoices.inv_id = invoice_line_items.inv_id
              INNER JOIN app.students
                INNER JOIN app.classes
                ON students.current_class = classes.class_id
              ON invoices.student_id = students.student_id
              WHERE invoices.inv_id = :invId
              ORDER BY fee_item
              ) q");
    $sth->execute( array(':invId' => $invId) );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'data' => $results ));
      $db = null;
    } else {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
      $db = null;
    }

  } catch(PDOException $e) {
    $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getExamTypes/:school/:class_cat_id', function ($school, $classCatId) {

    // Get all exam types

  $app = \Slim\Slim::getInstance();

    try
    {
    $db = setDBConnection($school);

    $sth = $db->prepare("SELECT exam_type_id, exam_type, exam_types.class_cat_id, class_cat_name
              FROM app.exam_types
              LEFT JOIN app.class_cats
              ON exam_types.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
              WHERE exam_types.class_cat_id = :classCatId
              ORDER BY sort_order");
    $sth->execute(array(':classCatId' => $classCatId));
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }

    } catch(PDOException $e) {
        $app->response()->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

?>
