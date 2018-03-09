<?php
$app->get('/getAllStudents/:status(/:startDate/:endDate)', function ($status,$startDate=null,$endDate=null) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    if( $startDate !== null )
    {
      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id,
                  (select array_agg(fee_items.fee_item_id)
                    from app.student_fee_items
                    inner join app.fee_items
                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                    where student_id = students.student_id
                    and optional is true) as enrolled_opt_courses
                 FROM app.students
                 INNER JOIN app.classes ON students.current_class = classes.class_id
                 WHERE students.active = :status
                 AND admission_date between :startDate and :endDate
                 ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status, ':startDate' => $startDate, ':endDate' => $endDate) );
    }
    else
    {
      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id,
                  (select array_agg(fee_items.fee_item_id)
                    from app.student_fee_items
                    inner join app.fee_items
                    on student_fee_items.fee_item_id = fee_items.fee_item_id
                    where student_id = students.student_id
                    and optional is true) as enrolled_opt_courses
                 FROM app.students
                 INNER JOIN app.classes ON students.current_class = classes.class_id
                 WHERE students.active = :status
                 ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status));
    }
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
        foreach( $results as $result)
        {
          $result->enrolled_opt_courses = pg_array_parse($result->enrolled_opt_courses);
        }

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

$app->get('/getAllParents', function () {
  //Show parents associated with teacher's students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT guardians.guardian_id,
                  guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                  email, telephone,
                  students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                  students.student_id, students.current_class, class_name, relationship
              FROM app.students
              INNER JOIN app.student_guardians
                INNER JOIN app.guardians
                ON student_guardians.guardian_id = guardians.guardian_id
              ON students.student_id = student_guardians.student_id AND student_guardians.active is true
              INNER JOIN app.classes
              ON students.current_class = classes.class_id AND students.active is true
              WHERE students.active is true
              ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
    $sth->execute();
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

$app->get('/getTeacherStudents/:teacher_id/:status(/:startDate/:endDate)', function ($teacherId, $status,$startDate=null,$endDate=null) {
  //Show teacher students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    if( $startDate !== null )
    {
      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id
               FROM app.students
               INNER JOIN app.classes
                INNER JOIN app.class_subjects
                  INNER JOIN app.subjects
                  ON class_subjects.subject_id = subjects.subject_id
                ON classes.class_id = class_subjects.class_id
               ON students.current_class = classes.class_id
               WHERE students.active = :status
               AND admission_date between :startDate and :endDate
               AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
               GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
               ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status, ':startDate' => $startDate, ':endDate' => $endDate) );
    }
    else
    {

      $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                  classes.teacher_id as class_teacher_id
               FROM app.students
               INNER JOIN app.classes
                INNER JOIN app.class_subjects
                  INNER JOIN app.subjects
                  ON class_subjects.subject_id = subjects.subject_id
                ON classes.class_id = class_subjects.class_id
               ON students.current_class = classes.class_id
               WHERE students.active = :status
               AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
               GROUP BY students.student_id, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type
               ORDER BY first_name, middle_name, last_name");
      $sth->execute( array(':status' => $status, ':teacherId' => $teacherId));
    }

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

$app->get('/getTeacherParents/:teacher_id', function ($teacherId) {
  //Show parents associated with teacher's students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("SELECT guardians.guardian_id,
                  guardians.first_name || ' ' || coalesce(guardians.middle_name,'') || ' ' || guardians.last_name AS parent_full_name,
                  email, telephone,
                  students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,
                  students.student_id, students.current_class, class_name, relationship
              FROM app.classes
              INNER JOIN app.students
                INNER JOIN app.student_guardians
                  INNER JOIN app.guardians
                  ON student_guardians.guardian_id = guardians.guardian_id
                ON students.student_id = student_guardians.student_id AND student_guardians.active is true
              ON classes.class_id = students.current_class AND students.active is true
              WHERE classes.active is true
              AND classes.teacher_id = :teacherId
              ORDER BY guardians.first_name, guardians.middle_name, guardians.last_name");
    $sth->execute( array(':teacherId' => $teacherId));
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

$app->get('/getStudentDetails/:studentId', function ($studentId) {
  //Show all students

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $sth = $db->prepare("SELECT students.*, classes.class_id, classes.class_cat_id, classes.class_name, classes.report_card_type,
                payment_plan_name || ' (' || num_payments || ' payments ' || payment_interval || ' ' || payment_interval2 || '(s) apart)' as payment_plan_name,
                classes.teacher_id as class_teacher_id
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
      // TO DO: I only want fee items for this school year?
      $sth4 = $db->prepare("SELECT
                  student_fee_item_id,
                  student_fee_items.fee_item_id,
                  fee_item, amount,
                  payment_method,
                  (select sum(payment_inv_items.amount)
                    from app.payment_inv_items
                    inner join app.invoice_line_items
                    on payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
                    where invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
                  ) as payment_made,
                  student_fee_items.active
                FROM app.student_fee_items
                INNER JOIN app.fee_items on student_fee_items.fee_item_id = fee_items.fee_item_id
                WHERE student_id = :studentID
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

$app->get('/getStudentBalance/:studentId', function ($studentId) {
  // Return students next payment

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();

    // get total amount of student fee items
    // calculate the amount due and due date
    // calculate the balance owing
    $sth = $db->prepare("SELECT fee_item, q.payment_method,
                          sum(invoice_total) AS total_due,
                          sum(total_paid) AS total_paid,
                          sum(total_paid) - sum(invoice_total) AS balance
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
                  COALESCE((SELECT sum(amount) from app.credits WHERE student_id = :studentID  ),0) AS total_credit,
                  (SELECT sum(balance) from app.invoice_balances2 WHERE student_id = :studentID AND due_date <= now()::date AND canceled = false) AS arrears
                  ");
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

      $feeSummary->total_due = ($balance ? $balance->total_due : 0);
      $feeSummary->total_paid = ($balance ? $balance->total_paid : 0);
      $feeSummary->balance = ($balance ? $balance->balance : 0);
      $feeSummary->past_due = ($balance ? $balance->past_due : false);

      $results = new stdClass();
      $results->fee_summary = $feeSummary;
      $results->fees = $fees;
    }

    if( $fees ) {
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

$app->get('/getStudentInvoices/:studentId', function ($studentId) {
  // Return students invoices

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();

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
                                    date_part('year',terms.start_date) as year
                          FROM app.invoice_balances2
                          INNER JOIN app.terms ON invoice_balances2.term_id = terms.term_id
                          WHERE student_id = :studentId
                          ORDER BY inv_date");
      $sth->execute( array(':studentId' => $studentId));
      $results = $sth->fetchAll(PDO::FETCH_OBJ);

      if($results) {

        foreach( $results as $result)
        {
          $result->invoice_line_items = pg_array_parse($result->invoice_items);
        }

        $app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array('response' => 'success', 'data' => $results ));
        $db = null;
      }
      else {
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

$app->get('/getOpenInvoices/:studentId', function ($studentId) {
  // Get all students open invoices

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $sth = $db->prepare("SELECT
                students.student_id,
                invoices.inv_id,
                first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                class_name, class_id, class_cat_id,
                inv_date,
                invoice_line_items.amount,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) as total_paid,
                coalesce((select sum(amount) from app.payment_inv_items where inv_item_id = invoice_line_items.inv_item_id),0) - invoice_line_items.amount as balance,
                due_date,
                inv_item_id,
                fee_item,
                invoice_line_items.amount as line_item_amount
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
              WHERE students.student_id = :studentId
              AND (select coalesce(sum(amount),0) - invoices.total_amount from app.payment_inv_items where inv_id = invoices.inv_id) < 0
              AND canceled = false
              ORDER BY inv_id, due_date, fee_item");
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

$app->get('/getStudentFeeItems/:studentId', function ($studentId) {
  // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    // TO DO: I only want fee items for this school year?
   $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount,
                  CASE WHEN frequency = 'per term' THEN 3
                       ELSE 1
                  END as frequency
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

$app->get('/getReplaceableFeeItems/:studentId', function ($studentId) {
  // Get all students replaceable fee items

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    // TO DO: I only want fee items for this school year?
     $sth = $db->prepare("SELECT student_fee_item_id, fee_item, amount
              FROM app.student_fee_items
              INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true
              WHERE student_id = :studentId
              AND student_fee_items.active = true
              AND replaceable = true
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
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->get('/getStudentPayments/:studentId', function ($studentId) {
  // Return students payments

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

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
                  amount - coalesce((select coalesce(sum(amount),0) as sum
                            from app.payment_inv_items
                            inner join app.invoices using (inv_id)
                            where payment_id = payments.payment_id
                            and canceled = false ),0)
                ),0) AS unapplied_amount
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

$app->get('/getStudentCredits/:studentId', function ($studentId) {
  // Return students credits

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    // get credits

    $sth = $db->prepare("SELECT credit_id, credits.amount, payment_date, credits.payment_id, payment_method, slip_cheque_no
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

$app->get('/getStudentArrears/:studentId/:date', function ($studentId, $date) {
  // Return students arrears for before a given date

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("select sum(total_paid - total_amount) as balance
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

    if($results && $results->balance !== null && $results->balance < 0 ) {
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

$app->get('/getStudentClasses/:studentId', function ($studentId) {
    // Get all students classes, present and past

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
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

$app->post('/addStudent', function () use($app) {
  // Add student
  $allPostVars = json_decode($app->request()->getBody(),true);

  $admissionNumber =        ( isset($allPostVars['admission_number']) ? $allPostVars['admission_number']: null);
  $gender =             ( isset($allPostVars['gender']) ? $allPostVars['gender']: null);
  $firstName =          ( isset($allPostVars['first_name']) ? $allPostVars['first_name']: null);
  $middleName =           ( isset($allPostVars['middle_name']) ? $allPostVars['middle_name']: null);
  $lastName =           ( isset($allPostVars['last_name']) ? $allPostVars['last_name']: null);
  $dob =              ( isset($allPostVars['dob']) ? $allPostVars['dob']: null);
  $studentCat =           ( isset($allPostVars['student_category']) ? $allPostVars['student_category']: null);
  $studentType =           ( isset($allPostVars['student_type']) ? $allPostVars['student_type']: null);
  $nationality =          ( isset($allPostVars['nationality']) ? $allPostVars['nationality']: null);
  $currentClass =         ( isset($allPostVars['current_class']) ? $allPostVars['current_class']: null);
  $studentImg =           ( isset($allPostVars['student_image']) ? $allPostVars['student_image']: null);
  $paymentMethod =        ( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
  $active =             ( isset($allPostVars['active']) ? $allPostVars['active']: true);
  $newStudent =           ( isset($allPostVars['new_student']) ? $allPostVars['new_student']: 'f');
  $admissionDate =        ( isset($allPostVars['admission_date']) ? $allPostVars['admission_date']: null);
  $marialStatusParents =      ( isset($allPostVars['marial_status_parents']) ? $allPostVars['marial_status_parents']: null);
  $adopted =            ( isset($allPostVars['adopted']) ? $allPostVars['adopted']: 'f');
  $adoptedAge =           ( isset($allPostVars['adopted_age']) ? $allPostVars['adopted_age']: null);
  $maritalSeparationAge =     ( isset($allPostVars['marital_separation_age']) ? $allPostVars['marital_separation_age']: null);
  $adoptionAware =        ( isset($allPostVars['adoption_aware']) ? $allPostVars['adoption_aware']: 'f');
  $comments =           ( isset($allPostVars['comments']) ? $allPostVars['comments']: null);
  $hasMedicalConditions =     ( isset($allPostVars['has_medical_conditions']) ? $allPostVars['has_medical_conditions']: 'f');
  $hospitalized =         ( isset($allPostVars['hospitalized']) ? $allPostVars['hospitalized']: 'f');
  $hospitalizedDesc =       ( isset($allPostVars['hospitalized_description']) ? $allPostVars['hospitalized_description']: null);
  $currentMedicalTreatment =    ( isset($allPostVars['current_medical_treatment']) ? $allPostVars['current_medical_treatment']: 'f');
  $currentMedicalTreatmentDesc =  ( isset($allPostVars['current_medical_treatment_description']) ? $allPostVars['current_medical_treatment_description']: null);
  $otherMedicalConditions =     ( isset($allPostVars['other_medical_conditions']) ? $allPostVars['other_medical_conditions']: 'f');
  $otherMedicalConditionsDesc =   ( isset($allPostVars['other_medical_conditions_description']) ? $allPostVars['other_medical_conditions_description']: null);
  $emergencyContact =       ( isset($allPostVars['emergency_name']) ? $allPostVars['emergency_name']: null);
  $emergencyRelation =      ( isset($allPostVars['emergency_relationship']) ? $allPostVars['emergency_relationship']: null);
  $emergencyPhone =         ( isset($allPostVars['emergency_telephone']) ? $allPostVars['emergency_telephone']: null);
  $kcpeMarks =         ( isset($allPostVars['kcpe_marks']) ? $allPostVars['kcpe_marks']: null);
  $schoolHouse =         ( isset($allPostVars['school_house']) ? $allPostVars['school_house']: null);
  $pickUpIndividual =       ( isset($allPostVars['pick_up_drop_off_individual']) ? $allPostVars['pick_up_drop_off_individual']: null);
  $installmentOption =      ( isset($allPostVars['installment_option']) ? $allPostVars['installment_option']: null);
  $routeId =                ( isset($allPostVars['route_id']) ? $allPostVars['route_id']: null);

  // guardian fields
  $guardianData =         ( isset($allPostVars['guardians']) ? $allPostVars['guardians']: null);


  // medical condition fields
  $medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);

  // fee item fields
  $feeItems = ( isset($allPostVars['feeItems']) ? $allPostVars['feeItems']: null);
  $optFeeItems = ( isset($allPostVars['optFeeItems']) ? $allPostVars['optFeeItems']: null);

  $createdBy = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  $hasGuardian = false;
  $hasGuardianId = false;
  $hasMedical = false;
  $hasFeeItems = false;
  $hasOptFeeItems = false;

  try
  {
    $db = getDB();

    $studentInsert = $db->prepare("INSERT INTO app.students(admission_number, gender, first_name, middle_name, last_name, dob, student_category,
                                student_type, nationality,
                                student_image, current_class, payment_method, active, created_by, admission_date, marial_status_parents,
                                adopted, adopted_age, marital_separation_age, adoption_aware, comments, medical_conditions, hospitalized,
                                hospitalized_description, current_medical_treatment, current_medical_treatment_description,
                                other_medical_conditions, other_medical_conditions_description,
                                emergency_name, emergency_relationship, emergency_telephone, pick_up_drop_off_individual,
                                installment_option_id, new_student, transport_route_id, kcpe_marks, school_house)
            VALUES(:admissionNumber,:gender,:firstName,:middleName,:lastName,:dob,:studentCat,:studentType,:nationality,:studentImg, :currentClass, :paymentMethod, :active, :createdBy,
          :admissionDate, :marialStatusParents, :adopted, :adoptedAge, :maritalSeparationAge, :adoptionAware, :comments, :hasMedicalConditions, :hospitalized,
          :hospitalizedDesc, :currentMedicalTreatment, :currentMedicalTreatmentDesc, :otherMedicalConditions, :otherMedicalConditionsDesc,
          :emergencyContact, :emergencyRelation, :emergencyPhone, :pickUpIndividual, :installmentOption, :newStudent, :routeId, :kcpeMarks, :schoolHouse);");

    $studentClassInsert = $db->prepare("INSERT INTO app.student_class_history(student_id,class_id,created_by)
                      VALUES(currval('app.students_student_id_seq'),:currentClass,:createdBy);");

    $query = $db->prepare("SELECT currval('app.students_student_id_seq') as student_id");
    $query2 = $db->prepare("SELECT currval('app.students_student_id_seq') as student_id, currval('app.guardians_guardian_id_seq') as guardian_id");

    if( $guardianData !== null && count($guardianData) > 0 )
    {
      $hasGuardian = true;
      // add contact
      $guardianInsert = $db->prepare("INSERT INTO app.guardians(first_name, middle_name, last_name, title, id_number, address, telephone, email,
                      marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
                      VALUES(:guardianFirstName, :guardianMiddleName, :guardianLastName, :guardianTitle, :guardianIdNumber, :guardianAddress,
                          :guardianTelephone, :guardianEmail, :guardianMaritalStatus, :guardianOccupation, :guardianEmployer,
                          :guardianEmployerAddress, :guardianWorkEmail, :guardianWorkPhone, :createdBy);");

      $guardianInsert2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
                      VALUES(currval('app.students_student_id_seq'), currval('app.guardians_guardian_id_seq'), :guardianRelationship,  :createdBy)");

      // if its an update
      $guardianUpdate = $db->prepare("UPDATE app.guardians
                  SET first_name = :guardianFirstName,
                    middle_name = :guardianMiddleName,
                    last_name = :guardianLastName,
                    title = :guardianTitle,
                    id_number = :guardianIdNumber,
                    address = :guardianAddress,
                    telephone = :guardianTelephone,
                    email = :guardianEmail,
                    marital_status = :guardianMaritalStatus,
                    occupation = :guardianOccupation,
                    employer =:guardianEmployer,
                    employer_address = :guardianEmployerAddress,
                    work_email = :guardianWorkEmail,
                    work_phone = :guardianWorkPhone,
                    modified_date = now(),
                    modified_by = :createdBy
                  WHERE guardian_id = :guardianId");

      $guardianUpdate2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
                      VALUES(currval('app.students_student_id_seq'), :guardianId, :guardianRelationship,  :createdBy)");

    }

    if( count($medicalConditions) > 0 )
    {
      $hasMedical = true;
      $conditionInsert = $db->prepare("INSERT INTO app.student_medical_history(student_id, illness_condition, age, comments, created_by)
            VALUES(currval('app.students_student_id_seq'),?,?,?,?);");

    }

    if( count($feeItems) > 0 || count($optFeeItems) > 0 )
    {
      $hasFeeItems = ( count($feeItems) > 0 ? true : false);
      $hasOptFeeItems = ( count($optFeeItems) > 0 ? true : false);
      $feesInsert = $db->prepare("INSERT INTO app.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by)
                    VALUES(currval('app.students_student_id_seq'),:feeItemID,:amount,:paymentMethod,:userId);");
    }

    $db->beginTransaction();

    $studentInsert->execute( array(':admissionNumber' => $admissionNumber,
              ':gender' => $gender,
              ':firstName' => $firstName,
              ':middleName' => $middleName,
              ':lastName' => $lastName,
              ':dob' => $dob,
              ':studentCat' => $studentCat,
              ':studentType' => $studentType,
              ':nationality' => $nationality,
              ':studentImg' => $studentImg,
              ':currentClass' => $currentClass,
              ':paymentMethod' => $paymentMethod,
              ':active' => $active,
              ':createdBy' => $createdBy,
              ':admissionDate' => $admissionDate,
              ':marialStatusParents' => $marialStatusParents,
              ':adopted' => $adopted,
              ':adoptedAge' => $adoptedAge,
              ':maritalSeparationAge' => $maritalSeparationAge,
              ':adoptionAware' => $adoptionAware,
              ':comments' => $comments,
              ':hasMedicalConditions' => $hasMedicalConditions,
              ':hospitalized' => $hospitalized,
              ':hospitalizedDesc' => $hospitalizedDesc,
              ':currentMedicalTreatment' => $currentMedicalTreatment,
              ':currentMedicalTreatmentDesc' => $currentMedicalTreatmentDesc,
              ':otherMedicalConditions' => $otherMedicalConditions,
              ':otherMedicalConditionsDesc' => $otherMedicalConditionsDesc,
              ':emergencyContact' => $emergencyContact,
              ':emergencyRelation' => $emergencyRelation,
              ':emergencyPhone' => $emergencyPhone,
              ':pickUpIndividual' => $pickUpIndividual,
              ':installmentOption' => $installmentOption,
              ':newStudent' => $newStudent,
              ':routeId' => $routeId,
              ':kcpeMarks' => $kcpeMarks,
              ':schoolHouse' => $schoolHouse
    ) );

    $studentClassInsert->execute(array(':currentClass' => $currentClass,':createdBy' => $createdBy));

    if( $hasGuardian )
    {
      foreach( $guardianData as $guardian )
      {
        $guardianTelephone =    ( isset($guardian['telephone']) ? $guardian['telephone']: null);
        $guardianEmail =      ( isset($guardian['email']) ? $guardian['email']: null);
        $guardianFirstName =    ( isset($guardian['first_name']) ? $guardian['first_name']: null);
        $guardianMiddleName =     ( isset($guardian['middle_name']) ? $guardian['middle_name']: null);
        $guardianLastName =     ( isset($guardian['last_name']) ? $guardian['last_name']: null);
        $guardianIdNumber =     ( isset($guardian['id_number']) ? $guardian['id_number']: null);
        $guardianRelationship =   ( isset($guardian['relationship']) ? $guardian['relationship']: null);
        $guardianTitle =      ( isset($guardian['title']) ? $guardian['title']: null);
        $guardianOccupation =     ( isset($guardian['occupation']) ? $guardian['occupation']: null);
        $guardianAddress =      ( isset($guardian['address']) ? $guardian['address']: null);
        $guardianMaritalStatus =  ( isset($guardian['marital_status']) ? $guardian['marital_status']: null);
        $guardianEmployer =     ( isset($guardian['employer']) ? $guardian['employer']: null);
        $guardianEmployerAddress =  ( isset($guardian['employer_address']) ? $guardian['employer_address']: null);
        $guardianWorkEmail =    ( isset($guardian['work_email']) ? $guardian['work_email']: null);
        $guardianWorkPhone =    ( isset($guardian['work_phone']) ? $guardian['work_phone']: null);

        if( isset($guardian['guardian_id']) )
        {
          $hasGuardianId = true;
          $guardianUpdate->execute(array( ':guardianId' => $guardian['guardian_id'],
                ':guardianFirstName' => $guardianFirstName,
                ':guardianMiddleName' => $guardianMiddleName,
                ':guardianLastName' => $guardianLastName,
                ':guardianTitle' => $guardianTitle,
                ':guardianIdNumber' => $guardianIdNumber,
                ':guardianAddress' => $guardianAddress,
                ':guardianTelephone' => $guardianTelephone,
                ':guardianEmail' => $guardianEmail,
                ':guardianMaritalStatus' => $guardianMaritalStatus,
                ':guardianOccupation' => $guardianOccupation,
                ':guardianEmployer' => $guardianEmployer,
                ':guardianEmployerAddress' => $guardianEmployerAddress,
                ':guardianWorkEmail' => $guardianWorkEmail,
                ':guardianWorkPhone' => $guardianWorkPhone,
                ':createdBy' => $createdBy) );
          $guardianUpdate2->execute(array(':guardianId' =>  $guardian['guardian_id'],
                          ':guardianRelationship' => $guardianRelationship,
                          ':createdBy' => $createdBy));
        }
        else
        {
          $guardianInsert->execute( array(':guardianFirstName' => $guardianFirstName,
                ':guardianMiddleName' => $guardianMiddleName,
                ':guardianLastName' => $guardianLastName,
                ':guardianTitle' => $guardianTitle,
                ':guardianIdNumber' => $guardianIdNumber,
                ':guardianAddress' => $guardianAddress,
                ':guardianTelephone' => $guardianTelephone,
                ':guardianEmail' => $guardianEmail,
                ':guardianMaritalStatus' => $guardianMaritalStatus,
                ':guardianOccupation' => $guardianOccupation,
                ':guardianEmployer' => $guardianEmployer,
                ':guardianEmployerAddress' => $guardianEmployerAddress,
                ':guardianWorkEmail' => $guardianWorkEmail,
                ':guardianWorkPhone' => $guardianWorkPhone,
                ':createdBy' => $createdBy
          ) );
          $guardianInsert2->execute(array(':guardianRelationship' => $guardianRelationship, ':createdBy' => $createdBy));
        }
      }

    }

    if( $hasMedical )
    {
      foreach($medicalConditions as $medicalCondition )
      {
        $conditionInsert->execute( array($medicalCondition['medical_condition'],
              $medicalCondition['age'],
              $medicalCondition['comments'],
              $createdBy
        ) );
      }
    }

    if( $hasFeeItems )
    {
      foreach( $feeItems as $feeItem )
      {
        $feesInsert->execute( array(
          ':feeItemID' => $feeItem['fee_item_id'],
          ':amount' => $feeItem['amount'],
          ':paymentMethod' => $feeItem['payment_method'],
          ':userId' => $createdBy)
        );
      }
    }

    if( $hasOptFeeItems )
    {

      foreach( $optFeeItems as $optFeeItem )
      {
        $feesInsert->execute( array(
          ':feeItemID' => $optFeeItem['fee_item_id'],
          ':amount' => $optFeeItem['amount'],
          ':paymentMethod' => $optFeeItem['payment_method'],
          ':userId' => $createdBy)
        );
      }
    }

    // if guardian id was sent, just grab student id, else grab both ids, if a guardian was saved
    if( $hasGuardianId || !$hasGuardian )
    {
      $query->execute();
      $newStudent = $query->fetch(PDO::FETCH_OBJ);
    }
    else if( $hasGuardian )
    {
      $query2->execute();
      $newStudent = $query2->fetch(PDO::FETCH_OBJ);
    }

    $db->commit();


    // if login data was passed
    if( $hasGuardian )
    {
      foreach( $guardianData as $guardian )
      {
        if( $guardian['login'] !== null && isset($guardian['login']['username']) )
        {
          $guardian['student_id'] = $newStudent->student_id;
          if( !isset($guardian['guardian_id']) ) $guardian['guardian_id'] = $newStudent->guardian_id;
          createParentLogin($guardian);
        }
      }
    }


    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;


  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateStudent', function () use($app) {
  // Update student
  $allPostVars = json_decode($app->request()->getBody(),true);

  $updateDetails = false;
  $updateFamily = false;
  $updateMedical = false;
  $updateFees = false;

  $studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  if( isset($allPostVars['details']) )
  {
    $updateDetails = true;
    $gender =         ( isset($allPostVars['details']['gender']) ? $allPostVars['details']['gender']: null);
    $firstName =      ( isset($allPostVars['details']['first_name']) ? $allPostVars['details']['first_name']: null);
    $middleName =       ( isset($allPostVars['details']['middle_name']) ? $allPostVars['details']['middle_name']: null);
    $lastName =       ( isset($allPostVars['details']['last_name']) ? $allPostVars['details']['last_name']: null);
    $dob =          ( isset($allPostVars['details']['dob']) ? $allPostVars['details']['dob']: null);
    $studentCat =       ( isset($allPostVars['details']['student_category']) ? $allPostVars['details']['student_category']: null);
    $studentType =           ( isset($allPostVars['details']['student_type']) ? $allPostVars['details']['student_type']: null);
    $nationality =      ( isset($allPostVars['details']['nationality']) ? $allPostVars['details']['nationality']: null);
    $currentClass =     ( isset($allPostVars['details']['current_class']) ? $allPostVars['details']['current_class']: null);
    $previousClass =    ( isset($allPostVars['details']['previous_class']) ? $allPostVars['details']['previous_class']: null);
    $currentClassCatId =  ( isset($allPostVars['details']['current_class_cat']) ? $allPostVars['details']['current_class_cat']: null);
    $previousClassCatId =   ( isset($allPostVars['details']['previous_class_cat']) ? $allPostVars['details']['previous_class_cat']: null);
    $updateClass =      ( isset($allPostVars['details']['update_class']) ? $allPostVars['details']['update_class']: false);
    $studentImg =       ( isset($allPostVars['details']['student_image']) ? $allPostVars['details']['student_image']: null);
    $active =         ( isset($allPostVars['details']['active']) ? $allPostVars['details']['active']: true);
    $newStudent =       ( isset($allPostVars['details']['new_student']) ? $allPostVars['details']['new_student']: 'f');
    $admissionNumber =    ( isset($allPostVars['details']['admission_number']) ? $allPostVars['details']['admission_number']: null);
    $admissionDate =    ( isset($allPostVars['details']['admission_date']) ? $allPostVars['details']['admission_date']: null);
    $kcpeMarks =          ( isset($allPostVars['details']['kcpe_marks']) ? $allPostVars['details']['kcpe_marks']: null);
    $schoolHouse =          ( isset($allPostVars['details']['school_house']) ? $allPostVars['details']['school_house']: null);
  }

  if( isset($allPostVars['family']) )
  {
    $updateFamily = true;
    $marialStatusParents =  ( isset($allPostVars['family']['marial_status_parents']) ? $allPostVars['family']['marial_status_parents']: null);
    $adopted =        ( isset($allPostVars['family']['adopted']) ? $allPostVars['family']['adopted']: 'f');
    $adoptedAge =       ( isset($allPostVars['family']['adopted_age']) ? $allPostVars['family']['adopted_age']: null);
    $maritalSeparationAge = ( isset($allPostVars['family']['marital_separation_age']) ? $allPostVars['family']['marital_separation_age']: null);
    $adoptionAware =    ( isset($allPostVars['family']['adoption_aware']) ? $allPostVars['family']['adoption_aware']: 'f');
    $emergencyContact =   ( isset($allPostVars['family']['emergency_name']) ? $allPostVars['family']['emergency_name']: null);
    $emergencyRelation =  ( isset($allPostVars['family']['emergency_relationship']) ? $allPostVars['family']['emergency_relationship']: null);
    $emergencyPhone =     ( isset($allPostVars['family']['emergency_telephone']) ? $allPostVars['family']['emergency_telephone']: null);
    $pickUpIndividual =   ( isset($allPostVars['family']['pick_up_drop_off_individual']) ? $allPostVars['family']['pick_up_drop_off_individual']: null);
  }

  if( isset($allPostVars['medical']) )
  {
    $updateMedical = true;
    $comments =           ( isset($allPostVars['medical']['comments']) ? $allPostVars['medical']['comments']: null);
    $hasMedicalConditions =     ( isset($allPostVars['medical']['has_medical_conditions']) ? $allPostVars['medical']['has_medical_conditions']: 'f');
    $hospitalized =         ( isset($allPostVars['medical']['hospitalized']) ? $allPostVars['medical']['hospitalized']: 'f');
    $hospitalizedDesc =       ( isset($allPostVars['medical']['hospitalized_description']) ? $allPostVars['medical']['hospitalized_description']: null);
    $currentMedicalTreatment =    ( isset($allPostVars['medical']['current_medical_treatment']) ? $allPostVars['medical']['current_medical_treatment']: 'f');
    $currentMedicalTreatmentDesc =  ( isset($allPostVars['medical']['current_medical_treatment_description']) ? $allPostVars['medical']['current_medical_treatment_description']: null);
    $otherMedicalConditions =     ( isset($allPostVars['medical']['other_medical_conditions']) ? $allPostVars['medical']['other_medical_conditions']: 'f');
    $otherMedicalConditionsDesc =   ( isset($allPostVars['medical']['other_medical_conditions_description']) ? $allPostVars['medical']['other_medical_conditions_description']: null);
  }

  if( isset($allPostVars['fees']) )
  {
    $updateFees = true;
    $paymentMethod =  ( isset($allPostVars['fees']['payment_method']) ? $allPostVars['fees']['payment_method']: null);
    $installmentOption =  ( isset($allPostVars['fees']['installment_option']) ? $allPostVars['fees']['installment_option']: null);
    $routeId =            ( isset($allPostVars['fees']['route_id']) ? $allPostVars['fees']['route_id']: null);
    $feeItems =     ( isset($allPostVars['fees']['feeItems']) ? $allPostVars['fees']['feeItems']: array());
    $optFeeItems =    ( isset($allPostVars['fees']['optFeeItems']) ? $allPostVars['fees']['optFeeItems']: array());
  }


  try
  {
    $db = getDB();

    if( $updateDetails )
    {
      $studentUpdate = $db->prepare(
        "UPDATE app.students
          SET gender = :gender,
            first_name = :firstName,
            middle_name = :middleName,
            last_name = :lastName,
            dob = :dob,
            student_category = :studentCat,
            student_type = :studentType,
            nationality = :nationality,
            student_image = :studentImg,
            current_class = :currentClass,
            active = :active,
            new_student = :newStudent,
            admission_date= :admissionDate,
            admission_number= :admissionNumber,
            modified_date = now(),
            modified_by = :userId,
            kcpe_marks = :kcpeMarks,
            school_house = :schoolHouse
          WHERE student_id = :studentId"
      );

      // if they changed the class, make entry into class history table
      if( $updateClass )
      {
        $classInsert1 = $db->prepare("UPDATE app.student_class_history SET end_date = now() WHERE student_id = :studentId AND class_id = :previousClass;");

        $classInsert2 = $db->prepare("
          INSERT INTO app.student_class_history(student_id,class_id,created_by)
          VALUES(:studentId,:currentClass,:createdBy);"
        );

        if( $currentClassCatId != $previousClassCatId )
        {
          // need to remove any students fee items associated with old class cat
          $feeItemUpdate = $db->prepare("UPDATE app.student_fee_items
                          SET active = false,
                            modified_date = now(),
                            modified_by = :userId
                          WHERE fee_item_id = any(SELECT fee_item_id FROM app.fee_items WHERE :previousClassCatId = any(class_cats_restriction) AND fee_items.active is true)
                          AND student_id = :studentId");
        }
      }

    }

    else if( $updateFamily )
    {
      $studentFamilyUpdate = $db->prepare(
        "UPDATE app.students
          SET marial_status_parents = :marialStatusParents,
            adopted = :adopted,
            adopted_age = :adoptedAge,
            marital_separation_age = :maritalSeparationAge,
            adoption_aware = :adoptionAware,
            emergency_name = :emergencyName,
            emergency_relationship = :emergencyRelationship,
            emergency_telephone = :emergencyTelephone,
            pick_up_drop_off_individual = :pickUpIndividual,
            modified_date = now(),
            modified_by = :userId
          WHERE student_id = :studentId"
      );
    }

    else if( $updateMedical )
    {
      $studentMedicalUpdate = $db->prepare(
        "UPDATE app.students
          SET medical_conditions = :hasMedicalConditions,
            hospitalized = :hospitalized,
            hospitalized_description = :hospitalizedDesc,
            current_medical_treatment = :currentMedicalTreatment,
            current_medical_treatment_description = :currentMedicalTreatmentDesc,
            other_medical_conditions = :otherMedicalConditions,
            other_medical_conditions_description = :otherMedicalConditionsDesc,
            modified_date = now(),
            modified_by = :userId
          WHERE student_id = :studentId"
      );
    }

    else if( $updateFees )
    {

      $studentUpdate = $db->prepare(
        "UPDATE app.students
          SET payment_method = :paymentMethod,
            installment_option_id = :installmentOption,
            active = true,
            transport_route_id = :routeId,
            modified_date = now(),
            modified_by = :userId
          WHERE student_id = :studentId"
      );

      // prepare the possible statements
      $feesUpdate = $db->prepare("UPDATE app.student_fee_items
                    SET amount = :amount,
                      payment_method = :paymentMethod,
                      active = true,
                      modified_date = now(),
                      modified_by = :userID
                    WHERE student_id = :studentId
                    AND fee_item_id = :feeItemId");

      $feesInsert = $db->prepare("INSERT INTO app.student_fee_items(student_id, fee_item_id, amount, payment_method, created_by)
                    VALUES(:studentId,:feeItemID,:amount,:paymentMethod,:userId);");

      $feeItemCheck = $db->prepare("SELECT count(invoice_line_items.inv_item_id) as num_invoices,
                        count(payment_inv_items.amount) as num_payments,
                        count(payment_replacement_items.amount) as num_replacement_payments
                      FROM app.student_fee_items
                      LEFT JOIN app.invoice_line_items
                        left join app.payment_inv_items
                        on invoice_line_items.inv_item_id = payment_inv_items.inv_item_id
                      ON student_fee_items.student_fee_item_id = invoice_line_items.student_fee_item_id
                      LEFT JOIN app.payment_replacement_items
                      ON student_fee_items.student_fee_item_id = payment_replacement_items.student_fee_item_id
                      WHERE student_id = :studentId
                      AND student_fee_items.student_fee_item_id = :studentFeeItemId
                      ");

      $deleteItem = $db->prepare("DELETE FROM app.student_fee_items WHERE student_id = :studentId AND fee_item_id = :feeItemId");

      $inactivate = $db->prepare("UPDATE app.student_fee_items
                    SET active = false,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE student_id = :studentId
                    AND fee_item_id = :feeItemId"
      );

      $reactivate = $db->prepare("UPDATE app.student_fee_items
                    SET active = true,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE student_id = :studentId
                    AND fee_item_id = :feeItemId"
      );


      // get what is already set for this student
      $query = $db->prepare("SELECT fee_item_id, student_fee_item_id FROM app.student_fee_items WHERE student_id = :studentId");
      $query->execute( array('studentId' => $studentId) );
      $currentFeeItems = $query->fetchAll(PDO::FETCH_OBJ);

    }


    $db->beginTransaction();

    if( $updateDetails )
    {
      $studentUpdate->execute( array(':studentId' => $studentId,
              ':gender' => $gender,
              ':firstName' => $firstName,
              ':middleName' => $middleName,
              ':lastName' => $lastName,
              ':dob' => $dob,
              ':studentCat' => $studentCat,
              ':studentType' => $studentType,
              ':nationality' => $nationality,
              ':studentImg' => $studentImg,
              ':currentClass' => $currentClass,
              ':active' => $active,
              ':newStudent' => $newStudent,
              ':admissionDate' => $admissionDate,
              ':admissionNumber' => $admissionNumber,
              ':userId' => $userId,
              ':kcpeMarks' => $kcpeMarks,
              ':schoolHouse' => $schoolHouse
      ) );
      if( $updateClass )
      {
        $classInsert1->execute(array('studentId' => $studentId, ':previousClass' => $previousClass));
        $classInsert2->execute(array('studentId' => $studentId, ':currentClass' => $currentClass,':createdBy' => $userId));

        if( $currentClassCatId != $previousClassCatId )
        {
          $feeItemUpdate->execute( array('studentId' => $studentId, ':previousClassCatId' => $previousClassCatId, ':userId' => $userId) );
        }
      }
    }

    else if( $updateFamily )
    {

      $studentFamilyUpdate->execute( array(':studentId' => $studentId,
              ':marialStatusParents' => $marialStatusParents,
              ':adopted' => $adopted,
              ':adoptedAge' => $adoptedAge,
              ':maritalSeparationAge' => $maritalSeparationAge,
              ':adoptionAware' => $adoptionAware,
              ':emergencyName' => $emergencyContact,
              ':emergencyRelationship' => $emergencyRelation,
              ':emergencyTelephone' => $emergencyPhone,
              ':pickUpIndividual' => $pickUpIndividual,
              ':userId' => $userId
      ) );
    }

    else if( $updateMedical )
    {
      $studentMedicalUpdate->execute( array(':studentId' => $studentId,
              ':hasMedicalConditions' => $hasMedicalConditions,
              ':hospitalized' => $hospitalized,
              ':hospitalizedDesc' => $hospitalizedDesc,
              ':currentMedicalTreatment' => $currentMedicalTreatment,
              ':currentMedicalTreatmentDesc' => $currentMedicalTreatmentDesc,
              ':otherMedicalConditions' => $otherMedicalConditions,
              ':otherMedicalConditionsDesc' => $otherMedicalConditionsDesc,
              ':userId' => $userId
      ) );
    }

    else if( $updateFees )
    {

      $studentUpdate->execute(array( ':paymentMethod' => $paymentMethod,
            ':installmentOption' => $installmentOption,
            ':routeId' => $routeId,
            ':userId' => $userId,
            ':studentId' => $studentId)
      );


      if( count($feeItems) > 0 )
      {
        // loop through and add or update
        foreach( $feeItems as $feeItem )
        {
          $amount =       ( isset($feeItem['amount']) ? $feeItem['amount']: null);
          $paymentMethod =  ( isset($feeItem['payment_method']) ? $feeItem['payment_method']: null);
          $studentFeeItemID = ( isset($feeItem['student_fee_item_id']) ? $feeItem['student_fee_item_id']: null);
          $feeItemId =    ( isset($feeItem['fee_item_id']) ? $feeItem['fee_item_id']: null);

          // this fee item exists, update it, else insert
          if( $studentFeeItemID !== null && !empty($studentFeeItemID) )
          {
            // TO DO: if we are changing the amount to less the current amount
            // and the item is on an invoice and already paid
            // we need to create a credit for difference
            $feesUpdate->execute(array(':amount' => $amount,
                          ':paymentMethod' => $paymentMethod,
                          ':userID' => $userId,
                          ':studentId' => $studentId,
                          ':feeItemId' => $feeItemId ));
          }
          else
          {
            $feesInsert->execute( array(
              ':studentId' => $studentId,
              ':feeItemID' => $feeItem['fee_item_id'],
              ':amount' => $feeItem['amount'],
              ':paymentMethod' => $feeItem['payment_method'],
              ':userId' => $userId)
            );

          }
        }
      }

      if( count($optFeeItems) > 0 )
      {
        // loop through and add or update
        foreach( $optFeeItems as $optFeeItem )
        {
          $amount =       ( isset($optFeeItem['amount']) ? $optFeeItem['amount']: null);
          $paymentMethod =  ( isset($optFeeItem['payment_method']) ? $optFeeItem['payment_method']: null);
          $studentFeeItemID = ( isset($optFeeItem['student_fee_item_id']) ? $optFeeItem['student_fee_item_id']: null);
          $feeItemId =    ( isset($optFeeItem['fee_item_id']) ? $optFeeItem['fee_item_id']: null);

          // this fee item exists, update it, else insert
          if( $studentFeeItemID !== null && !empty($studentFeeItemID) )
          {
            // TO DO: if we are changing the amount to less the curernt amount
            // and the item is on an invoice and already paid
            // we need to create a credit for difference
            $feesUpdate->execute(array(':amount' => $amount, ':paymentMethod' => $paymentMethod, ':userID' => $userId, ':studentId' => $studentId, ':feeItemId' => $feeItemId ));

          }
          else
          {
            $feesInsert->execute( array(
              ':studentId' => $studentId,
              ':feeItemID' => $optFeeItem['fee_item_id'],
              ':amount' => $optFeeItem['amount'],
              ':paymentMethod' => $optFeeItem['payment_method'],
              ':userId' => $userId)
            );
          }
        }
      }

      // look for fee items to remove
      // compare to what was passed in
      foreach( $currentFeeItems as $currentFeeItem )
      {
        $deleteMe = true;
        // if found, do not delete
        foreach( $feeItems as $feeItem )
        {
          if( isset($feeItem['fee_item_id']) && $feeItem['fee_item_id'] == $currentFeeItem->fee_item_id )
          {
            $deleteMe = false;
          }
        }
        foreach( $optFeeItems as $optFeeItem )
        {
          if( isset($optFeeItem['fee_item_id']) &&  $optFeeItem['fee_item_id'] == $currentFeeItem->fee_item_id )
          {
            $deleteMe = false;
          }
        }

        if( $deleteMe )
        {

          // if there is a payment, mark inactive
          // if there is an invoice item, mark inactive
          // if no payments or invoice, delete

          $feeItemCheck->execute( array(':studentId' => $studentId, ':studentFeeItemId' => $currentFeeItem->student_fee_item_id) );
          $checkResults = $feeItemCheck->fetch(PDO::FETCH_OBJ);

          if( $checkResults->num_invoices > 0 || $checkResults->num_payments > 0 || $checkResults->num_replacement_payments > 0 )
          {
            $inactivate->execute(array(':studentId' => $studentId, ':feeItemId' => $currentFeeItem->fee_item_id, ':userId' => $userId));
          }
          else
          {
            // no invoices or payments, delete
            $deleteItem->execute( array(':studentId' => $studentId, ':feeItemId' => $currentFeeItem->fee_item_id) );
          }
        }
      }
    }

    $db->commit();

    $results = new Stdclass();
    if( $updateDetails && $previousClass != $currentClass )
    {
      // updating class could impact previously entered exam marks for this year
      // check if any are entered
      $examCheck = $db->prepare("SELECT exam_id
                    FROM app.exam_marks
                    WHERE student_id = :studentId
                    AND (select date_part('year',start_date) from app.terms where terms.term_id = exam_marks.term_id) = date_part('year', now())");
      $examCheck->execute( array('studentId' => $studentId) );
      $results = $examCheck->fetchAll(PDO::FETCH_OBJ);
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "data" => $results));
    $db = null;
  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getAllGuardians(/:status)', function ($status=true) {
  // Get all guardians

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $sth = $db->prepare("SELECT *, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name
              FROM app.guardians
              WHERE active = :status
              ORDER BY first_name, middle_name, last_name");
    $sth->execute( array(':status' => $status) );
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

$app->post('/addGuardian', function () use($app) {
  // Add guardian
  $allPostVars = json_decode($app->request()->getBody(),true);

  $studentId =      ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $guardianId =     ( isset($allPostVars['guardian_id']) ? $allPostVars['guardian_id']: null);

  // guardian fields
  $Telephone =      ( isset($allPostVars['guardian']['telephone']) ? $allPostVars['guardian']['telephone']: null);
  $Email =        ( isset($allPostVars['guardian']['email']) ? $allPostVars['guardian']['email']: null);
  $FirstName =      ( isset($allPostVars['guardian']['first_name']) ? $allPostVars['guardian']['first_name']: null);
  $MiddleName =       ( isset($allPostVars['guardian']['middle_name']) ? $allPostVars['guardian']['middle_name']: null);
  $LastName =       ( isset($allPostVars['guardian']['last_name']) ? $allPostVars['guardian']['last_name']: null);
  $IdNumber =       ( isset($allPostVars['guardian']['id_number']) ? $allPostVars['guardian']['id_number']: null);
  $Relationship =     ( isset($allPostVars['guardian']['relationship']) ? $allPostVars['guardian']['relationship']: null);
  $Title =        ( isset($allPostVars['guardian']['title']) ? $allPostVars['guardian']['title']: null);
  $Occupation =       ( isset($allPostVars['guardian']['occupation']) ? $allPostVars['guardian']['occupation']: null);
  $Address =        ( isset($allPostVars['guardian']['address']) ? $allPostVars['guardian']['address']: null);
  $MaritalStatus =    ( isset($allPostVars['guardian']['marital_status']) ? $allPostVars['guardian']['marital_status']: null);
  $Employer =       ( isset($allPostVars['guardian']['employer']) ? $allPostVars['guardian']['employer']: null);
  $EmployerAddress =    ( isset($allPostVars['guardian']['employer_address']) ? $allPostVars['guardian']['employer_address']: null);
  $WorkEmail =      ( isset($allPostVars['guardian']['work_email']) ? $allPostVars['guardian']['work_email']: null);
  $WorkPhone =      ( isset( $allPostVars['guardian']['work_phone']) ? $allPostVars['guardian']['work_phone']: null);

  $createdBy =      ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  $login =        ( isset($allPostVars['guardian']['login']) ? $allPostVars['guardian']['login']: null);


  try
  {
    $db = getDB();


    if( $guardianId !== null )
    {
      // using existing guardian for student
      $update = $db->prepare("UPDATE app.guardians
                  SET first_name = :FirstName,
                    middle_name = :MiddleName,
                    last_name = :LastName,
                    title = :Title,
                    id_number = :IdNumber,
                    address = :Address,
                    telephone = :Telephone,
                    email = :Email,
                    marital_status = :MaritalStatus,
                    occupation = :Occupation,
                    employer =:Employer,
                    employer_address = :EmployerAddress,
                    work_email = :WorkEmail,
                    work_phone = :WorkPhone,
                    modified_date = now(),
                    modified_by = :createdBy
                  WHERE guardian_id = :guardianId");

      $insert = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
                      VALUES(:studentId, :guardianId, :Relationship,  :createdBy)");

      $db->beginTransaction();
      $update->execute( array( ':guardianId' => $guardianId,
              ':FirstName' => $FirstName,
              ':MiddleName' => $MiddleName,
              ':LastName' => $LastName,
              ':Title' => $Title,
              ':IdNumber' => $IdNumber,
              ':Address' => $Address,
              ':Telephone' => $Telephone,
              ':Email' => $Email,
              ':MaritalStatus' => $MaritalStatus,
              ':Occupation' => $Occupation,
              ':Employer' => $Employer,
              ':EmployerAddress' => $EmployerAddress,
              ':WorkEmail' => $WorkEmail,
              ':WorkPhone' => $WorkPhone,
              ':createdBy' => $createdBy
      ) );
      $insert->execute(array(':Relationship' => $Relationship, ':guardianId' => $guardianId, ':studentId' => $studentId, ':createdBy' => $createdBy));
      $db->commit();
      $guardian_id = $guardianId;
    }
    else
    {
      // add new guardian and add to student
      $insert = $db->prepare("INSERT INTO app.guardians( first_name, middle_name, last_name, title, id_number, address, telephone, email,
                        marital_status, occupation, employer, employer_address, work_email, work_phone, created_by)
                  VALUES(:FirstName, :MiddleName, :LastName, :Title, :IdNumber, :Address,
                      :Telephone, :Email, :MaritalStatus, :Occupation, :Employer, :EmployerAddress, :WorkEmail,
                      :WorkPhone, :createdBy);");

      $insert2 = $db->prepare("INSERT INTO app.student_guardians(student_id, guardian_id, relationship, created_by)
                      VALUES(:studentId, currval('app.guardians_guardian_id_seq'), :Relationship, :createdBy)");

      $query = $db->prepare("SELECT currval('app.guardians_guardian_id_seq') as guardian_id");

      $db->beginTransaction();
      $insert->execute( array(
              ':FirstName' => $FirstName,
              ':MiddleName' => $MiddleName,
              ':LastName' => $LastName,
              ':Title' => $Title,
              ':IdNumber' => $IdNumber,
              ':Address' => $Address,
              ':Telephone' => $Telephone,
              ':Email' => $Email,
              ':MaritalStatus' => $MaritalStatus,
              ':Occupation' => $Occupation,
              ':Employer' => $Employer,
              ':EmployerAddress' => $EmployerAddress,
              ':WorkEmail' => $WorkEmail,
              ':WorkPhone' => $WorkPhone,
              ':createdBy' => $createdBy
      ) );
      $insert2->execute(array(':Relationship' => $Relationship, ':studentId' => $studentId, ':createdBy' => $createdBy));
      $query->execute();
      $db->commit();
      $result = $query->fetch(PDO::FETCH_OBJ);
      $guardian_id = $result->guardian_id;
    }

     $db = null;

    // if login data was passed
    if( $login !== null && isset($login['username']) )
    {
      $allPostVars['guardian']['student_id'] = $studentId;
      $allPostVars['guardian']['guardian_id'] = $guardian_id;
      createParentLogin($allPostVars['guardian']);
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "data" => $guardian_id));

  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
  $app->response()->headers->set('Content-Type', 'application/json');
  echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateGuardian', function () use($app) {
  // update guardian
  $allPostVars = json_decode($app->request()->getBody(),true);

  $guardianId =     ( isset($allPostVars['guardian']['guardian_id']) ? $allPostVars['guardian']['guardian_id']: null);
  $studentId =      ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);

  // guardian fields
  $Telephone =      ( isset($allPostVars['guardian']['telephone']) ? $allPostVars['guardian']['telephone']: null);
  $Email =        ( isset($allPostVars['guardian']['email']) ? $allPostVars['guardian']['email']: null);
  $FirstName =      ( isset($allPostVars['guardian']['first_name']) ? $allPostVars['guardian']['first_name']: null);
  $MiddleName =       ( isset($allPostVars['guardian']['middle_name']) ? $allPostVars['guardian']['middle_name']: null);
  $LastName =       ( isset($allPostVars['guardian']['last_name']) ? $allPostVars['guardian']['last_name']: null);
  $IdNumber =       ( isset($allPostVars['guardian']['id_number']) ? $allPostVars['guardian']['id_number']: null);
  $Relationship =     ( isset($allPostVars['guardian']['relationship']) ? $allPostVars['guardian']['relationship']: null);
  $Title =        ( isset($allPostVars['guardian']['title']) ? $allPostVars['guardian']['title']: null);
  $Occupation =       ( isset($allPostVars['guardian']['occupation']) ? $allPostVars['guardian']['occupation']: null);
  $Address =        ( isset($allPostVars['guardian']['address']) ? $allPostVars['guardian']['address']: null);
  $MaritalStatus =    ( isset($allPostVars['guardian']['marital_status']) ? $allPostVars['guardian']['marital_status']: null);
  $Employer =       ( isset($allPostVars['guardian']['employer']) ? $allPostVars['guardian']['employer']: null);
  $EmployerAddress =    ( isset($allPostVars['guardian']['employer_address']) ? $allPostVars['guardian']['employer_address']: null);
  $WorkEmail =      ( isset($allPostVars['guardian']['work_email']) ? $allPostVars['guardian']['work_email']: null);
  $WorkPhone =      ( isset( $allPostVars['guardian']['work_phone']) ? $allPostVars['guardian']['work_phone']: null);

  $userId =         ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  $login =        ( isset($allPostVars['guardian']['login']) ? $allPostVars['guardian']['login']: null);

  try
  {
    $db = getDB();

    $sth1 = $db->prepare("UPDATE app.guardians
                SET first_name = :FirstName,
                  middle_name = :MiddleName,
                  last_name =:LastName,
                  title = :Title,
                  id_number = :IdNumber,
                  address = :Address,
                  telephone = :Telephone,
                  email = :Email,
                  marital_status = :MaritalStatus,
                  occupation = :Occupation,
                  employer = :Employer,
                  employer_address = :EmployerAddress,
                  work_email = :WorkEmail,
                  work_phone = :WorkPhone,
                  modified_date = now(),
                  modified_by = :userId
                WHERE guardian_id = :guardianId"
                );
    $sth2 = $db->prepare("UPDATE app.student_guardians
                SET relationship= :Relationship,
                  modified_date = now(),
                  modified_by = :userId
                 WHERE student_id = :studentId
                 AND guardian_id = :guardianId");

    $db->beginTransaction();
    $sth1->execute( array(':guardianId' => $guardianId,
            ':FirstName' => $FirstName,
            ':MiddleName' => $MiddleName,
            ':LastName' => $LastName,
            ':Title' => $Title,
            ':IdNumber' => $IdNumber,
            ':Address' => $Address,
            ':Telephone' => $Telephone,
            ':Email' => $Email,
            ':MaritalStatus' => $MaritalStatus,
            ':Occupation' => $Occupation,
            ':Employer' => $Employer,
            ':EmployerAddress' => $EmployerAddress,
            ':WorkEmail' => $WorkEmail,
            ':WorkPhone' => $WorkPhone,
            ':userId' => $userId
    ) );
    $sth2->execute(array(':guardianId' => $guardianId, ':studentId' => $studentId, ':Relationship' => $Relationship,':userId' => $userId));
    $db->commit();
    $db = null;

    // if login data was passed
    if( $login !== null && isset($login['username']) )
    {
      $allPostVars['guardian']['student_id'] = $studentId;
      createParentLogin($allPostVars['guardian']);
    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));



  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->delete('/deleteGuardian/:student_id/:guardian_id', function ($studentId,$guardianId) {
  // delete guardian

  $app = \Slim\Slim::getInstance();

  try
  {
    // remove from client database
    $db = getDB();
    $sth = $db->prepare("DELETE FROM app.student_guardians WHERE guardian_id = :guardianId AND student_id = :studentId");
    $sth->execute( array(':guardianId' => $guardianId, ':studentId' => $studentId) );
    $db = null;

    // if they have a parent login, remove association with student
    $subdomain = getSubDomain();
    $db2 = getLoginDB();
    $sth2 = $db2->prepare("DELETE FROM parent_students WHERE guardian_id = :guardianId AND student_id = :studentId AND subdomain = :subdomain");
    $sth2->execute( array(':guardianId' => $guardianId, ':studentId' => $studentId, ':subdomain' => $subdomain) );
    $db2 = null;

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->post('/addMedicalConditions', function () use($app) {
  // Add medical conditions
  $allPostVars = json_decode($app->request()->getBody(),true);

  $studentId =  ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $userId =     ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  // medical fields
  $medicalConditions = ( isset($allPostVars['medicalConditions']) ? $allPostVars['medicalConditions']: null);

  try
  {
    $db = getDB();

    $studentUpdate = $db->prepare("UPDATE app.students SET medical_conditions = true WHERE student_id = :studentId");
    $conditionInsert = $db->prepare("INSERT INTO app.student_medical_history(student_id, illness_condition, age, comments, created_by)
    VALUES(?,?,?,?,?);");
    $query = $db->prepare("SELECT currval('app.student_medical_history_medical_id_seq') as medical_id, now() as date_medical_added");


    $results = array();
    // loop through the medical conditions and insert
    // place the resulting id in array for return
    foreach( $medicalConditions as $medicalCondition )
    {
      $db->beginTransaction();
      $studentUpdate->execute(array(':studentId' => $studentId));
      $conditionInsert->execute( array(
            $studentId,
            $medicalCondition['illness_condition'],
            $medicalCondition['age'],
            $medicalCondition['comments'],
            $userId
      ) );
      $query->execute();
      $db->commit();

      $results[] = $query->fetch(PDO::FETCH_OBJ);

    }

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "data" => $results));
    $db = null;
  } catch(PDOException $e) {
    $db->rollBack();
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->put('/updateMedicalConditions', function () use($app) {
  // update medical condition
  $allPostVars = json_decode($app->request()->getBody(),true);

  $medicalId =    ( isset($allPostVars['medicalCondition']['medical_id']) ? $allPostVars['medicalCondition']['medical_id']: null);
  $illnessCondition = ( isset($allPostVars['medicalCondition']['illness_condition']) ? $allPostVars['medicalCondition']['illness_condition']: null);
  $age =        ( isset($allPostVars['medicalCondition']['age']) ? $allPostVars['medicalCondition']['age']: null);
  $comments =     ( isset($allPostVars['medicalCondition']['comments']) ? $allPostVars['medicalCondition']['comments']: null);
  $userId =       ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = getDB();

    $sth = $db->prepare("UPDATE app.student_medical_history
                SET illness_condition = :illnessCondition,
                  age = :age,
                  comments =:comments,
                  modified_date = now(),
                  modified_by = :userId
                WHERE medical_id = :medicalId"
                );

    $sth->execute( array(':medicalId' => $medicalId,
            ':illnessCondition' => $illnessCondition,
            ':age' => $age,
            ':comments' => $comments,
            ':userId' => $userId
    ) );

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;
  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});

$app->delete('/deleteMedicalCondition/:medical_id', function ($medicalId) {
  // delete guardian

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();

    $sth = $db->prepare("DELETE FROM app.student_medical_history WHERE medical_id = :medicalId");

    $sth->execute( array(':medicalId' => $medicalId) );

    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;

  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }

});

$app->get('/getGuardiansChildren/:guardian_id', function ($guardianId) {
  // Get all children associated with a guardian

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
      $sth = $db->prepare("SELECT students.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name
              FROM app.student_guardians
              INNER JOIN app.students ON student_guardians.student_id = students.student_id
              WHERE guardian_id = :guardianId
              ORDER BY students.first_name, students.middle_name, students.last_name");
    $sth->execute( array(':guardianId' => $guardianId) );
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

$app->get('/getMISLogin/:id_number', function ($idNumber) {
  // Get mis login for id number

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getLoginDB();
    $sth = $db->prepare("SELECT parent_id, first_name, middle_name, last_name, email, id_number, active as login_active, username,
                  first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS parent_full_name,
                  (SELECT array_agg(student_id) FROM parent_students WHERE parent_id = parents.parent_id) as student_ids
              FROM parents
              WHERE id_number = :idNumber");
    $sth->execute( array(':idNumber' => $idNumber) );
    $results = $sth->fetch(PDO::FETCH_OBJ);

    if($results) {

      // convert pgarray to php array
      $results->student_ids = pg_array_parse($results->student_ids);

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

$app->get('/checkUsername/:username', function ($username) {
  // Check that username is unique

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getLoginDB();
    $sth = $db->prepare("SELECT parent_id
              FROM parents
              WHERE username = :username");
    $sth->execute( array(':username' => $username) );
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

$app->get('/checkIdNumber/:id_number', function ($idNumber) {
  // Check that id number is unique

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $sth = $db->prepare("SELECT guardian_id
              FROM app.guardians
              WHERE id_number = :idNumber");
    $sth->execute( array(':idNumber' => $idNumber) );
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

$app->get('/checkAdmNumber/:admission_number', function ($admissionNumber) {
  // Check that admission number is unique

  $app = \Slim\Slim::getInstance();

  try
  {
    $db = getDB();
    $sth = $db->prepare("SELECT student_id
              FROM app.students
              WHERE admission_number = :admissionNumber");
    $sth->execute( array(':admissionNumber' => $admissionNumber) );
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

$app->delete('/adminDeleteStudent/:secret/:student_id', function ($secret,$studentId) {
  // delete student and all associated records

  $app = \Slim\Slim::getInstance();
  try
  {
    require('../lib/token.php');
    if( $secret == $_studentDeleteToken )
    {
      $db = getDB();
      // get students school name and guardian ids for deleting from parents portal tables
      $studentDetails = $db->prepare("SELECT guardian_id, (SELECT value FROM app.settings WHERE name = 'School Abbr') as subdomain
                                    FROM app.student_guardians
                                    WHERE student_id = :studentId");

      /* delete payments */
      $d1 = $db->prepare("DELETE FROM app.payment_inv_items
                  WHERE inv_id in (select inv_id
                            from app.invoices
                            where student_id = :studentId)
                ");
      $d2 = $db->prepare("DELETE FROM app.payments WHERE student_id = :studentId");

      $d3 = $db->prepare("DELETE FROM app.payment_replacement_items
                  WHERE student_fee_item_id in (select student_fee_item_id
                            from app.student_fee_items
                            where student_id = :studentId)
                ");

      /* delete invoices */
      $d4 = $db->prepare("DELETE FROM app.invoice_line_items
                  WHERE inv_id in (select inv_id
                            from app.invoices
                            where student_id = :studentId)
                ");
      $d5 = $db->prepare("DELETE FROM app.invoices WHERE student_id = :studentId");

      /* delete report cards and exam marks */
      $d6 = $db->prepare("DELETE FROM app.report_cards WHERE student_id = :studentId");
      $d7 = $db->prepare("DELETE FROM app.exam_marks WHERE student_id = :studentId");

      /* delete student data */
      $d8 = $db->prepare("DELETE FROM app.student_class_history WHERE student_id = :studentId");
      $d9 = $db->prepare("DELETE FROM app.student_fee_items WHERE student_id = :studentId");
      $d10 = $db->prepare("DELETE FROM app.student_guardians WHERE student_id = :studentId");
      $d11 = $db->prepare("DELETE FROM app.student_medical_history WHERE student_id = :studentId");
      $d12 = $db->prepare("DELETE FROM app.students WHERE student_id = :studentId");

      $params = array(':studentId' => $studentId);

      $db->beginTransaction();
      $studentDetails->execute($params);
      $guardians = $studentDetails->fetchAll(PDO::FETCH_OBJ);

      $d1->execute( $params );
      $d2->execute( $params );
      $d3->execute( $params );
      $d4->execute( $params );
      $d5->execute( $params );
      $d6->execute( $params );
      $d7->execute( $params );
      $d8->execute( $params );
      $d9->execute( $params );
      $d10->execute( $params );
      $d11->execute( $params );
      $d12->execute( $params );


      // check if guardian is associated with other students, if not, delete
      $guardianCheck = $db->prepare("SELECT * FROM app.student_guardians WHERE guardian_id = :guardianId");
      $deleteGuardian = $db->prepare("DELETE FROM app.guardians WHERE guardian_id = :guardianId");
      forEach($guardians as $guardian)
      {
        $guardianCheck->execute( array(':guardianId' => $guardian->guardian_id) );
        $students = $guardianCheck->fetchAll(PDO::FETCH_OBJ);
        if(!$students)
        {
          $deleteGuardian->execute( array(':guardianId' => $guardian->guardian_id) );
        }
      }
      $db->commit();
      $db = null;


      /* delete from eduweb_mis */
      $db = getLoginDB();

      $subdomain = $guardians[0]->subdomain;

      $getParent = $db->prepare("SELECT parent_id FROM parent_students WHERE subdomain = :subdomain AND guardian_id = :guardianId");
      $deleteParentStudent = $db->prepare("DELETE FROM parent_students WHERE subdomain = :subdomain AND student_id = :studentId");
      $deleteParent = $db->prepare("DELETE FROM parents WHERE parent_id = :parentId");

      $db->beginTransaction();
      forEach($guardians as $guardian)
      {
        // grab parent id of guardian
        $getParent->execute( array(':subdomain' => $subdomain, ':guardianId' => $guardian->guardian_id) );
        $parents = $getParent->fetchAll(PDO::FETCH_OBJ);

        $deleteParentStudent->execute( array(':subdomain' => $subdomain, ':studentId' => $studentId) );

        if( $parents && count($parents) == 1 )
        {
          // only one student, delete and delete parent record
          $deleteParent->execute( array(':parentId' => $parents[0]->parent_id) );
        }
      }
      $db->commit();

      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "success", "code" => 1));
      $db = null;
    }
    else
    {
      $app->response->setStatus(200);
      $app->response()->headers->set('Content-Type', 'application/json');
      echo json_encode(array("response" => "failed", "code" => 2));
    }
  } catch(PDOException $e) {

    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
  }
});

$app->put('/promoteStudents', function () use($app) {
  // promote students
  $allPostVars = json_decode($app->request()->getBody(),true);

  $studentIds         =   ( isset($allPostVars['students']) ? $allPostVars['students']: null);
  $classId            =   ( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
  $previousClassId    =   ( isset($allPostVars['previous_class_id']) ? $allPostVars['previous_class_id']: null);
  $previousClassCatId =   ( isset($allPostVars['previous_class_cat_id']) ? $allPostVars['previous_class_cat_id']: null);
  $userId             =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
  {
    $db = getDB();

    $update = $db->prepare("UPDATE app.students
                            SET current_class = :classId,
                                modified_date = now(),
                                modified_by = :userId
                            WHERE student_id = :studentId");

    $classUpdate = $db->prepare("UPDATE app.student_class_history
                                  SET end_date = now()
                                  WHERE student_id = :studentId
                                  AND class_id = :previousClassId;");

    $classInsert = $db->prepare("
      INSERT INTO app.student_class_history(student_id,class_id,created_by)
      VALUES(:studentId,:classId,:userId);"
    );

    // need to remove any students fee items associated with old class cat
    $feeItemUpdate = $db->prepare("UPDATE app.student_fee_items
                    SET active = false,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE fee_item_id = any(SELECT fee_item_id FROM app.fee_items WHERE :previousClassCatId = any(class_cats_restriction) AND fee_items.active is true)
                    AND student_id = :studentId");

    $db->beginTransaction();
    foreach($studentIds as $studentId){
      $update->execute( array(':studentId' => $studentId,
            ':classId' => $classId,
            ':userId' => $userId
      ) );

      $classUpdate->execute( array(':studentId' => $studentId, ':previousClassId' => $previousClassId ) );
      $classInsert->execute( array(':studentId' => $studentId, ':classId' => $classId, ':userId' => $userId ) );
      $feeItemUpdate->execute( array(':studentId' => $studentId, ':previousClassCatId' => $previousClassCatId, ':userId' => $userId ) );
    }
    $db->commit();


    $app->response->setStatus(200);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo json_encode(array("response" => "success", "code" => 1));
    $db = null;
  } catch(PDOException $e) {
    $app->response()->setStatus(404);
    $app->response()->headers->set('Content-Type', 'application/json');
    echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
}

});


?>
