<?php

$app->get('/getAllClasses(/:status)', function ($status = true) {
    //Show all classes
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_id, class_name, class_cat_id, classes.teacher_id, classes.active, report_card_type,
									first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name
							FROM app.classes
							LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
							WHERE classes.active = :status
							ORDER BY sort_order"); 
       $sth->execute( array(':status' => $status ) );
 
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
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getClasses/(:classCatid/:status)', function ($classCatid = 'ALL', $status=true) {
	//Show classes for specific class category
	
	$app = \Slim\Slim::getInstance();

	try 
	{
		$db = getDB();
		$params = array(':status' => $status);
		$query = "SELECT class_id, class_name, classes.class_cat_id, teacher_id, classes.active, class_cat_name,
					classes.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, report_card_type,
					(select array_agg(subject_name order by sort_order) 
						from (
							select subject_name, sort_order
							from app.class_subjects 
							inner join app.subjects on class_subjects.subject_id = subjects.subject_id and subjects.active is true
							where class_subjects.class_id = classes.class_id 
							and class_subjects.active is true
							group by subject_name, sort_order
						)a ) as subjects
            FROM app.classes
			INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
			LEFT JOIN app.employees ON classes.teacher_id = employees.emp_id
			WHERE classes.active = :status
			";
			
		if( $classCatid != 'ALL' )
		{
			$query .= "AND classes.class_cat_id = :classCatid";
			$params[':classCatid'] = $classCatid;
		}
		
		$query .= " ORDER BY sort_order";
		
		$sth = $db->prepare($query);
		$sth->execute( $params );

		$results = $sth->fetchAll(PDO::FETCH_OBJ);

		if($results) {
		
			foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
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
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));        
	}

});

$app->get('/getTeacherClasses/:teacher_id(/:status)', function ($teacherId, $status = true) {
    //Show classes for specific teacher
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$sth = $db->prepare("SELECT classes.class_id, class_name, classes.class_cat_id, classes.teacher_id, classes.active, class_cat_name,
					first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name,
					(select array_agg(distinct subject_name) 
						from app.class_subjects 
						inner join app.subjects on class_subjects.subject_id = subjects.subject_id
						where class_subjects.class_id = classes.class_id
						and class_subjects.active is true
						and teacher_id = :teacherId) as subjects,
					(select count(*) 
								from app.students 
								inner join app.classes c
								on students.current_class = c.class_id  AND c.active is true 
								where c.class_id = classes.class_id) as num_students,
								blog_id, blog_name, report_card_type
					FROM app.classes
					INNER JOIN app.class_subjects
						INNER JOIN app.subjects
						ON class_subjects.subject_id = subjects.subject_id
					ON classes.class_id = class_subjects.class_id
					LEFT JOIN app.employees 
					ON classes.teacher_id = employees.emp_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					LEFT JOIN app.blogs 
					ON (classes.class_id = blogs.class_id AND classes.teacher_id = blogs.teacher_id) OR
					   (class_subjects.class_id = blogs.class_id AND subjects.teacher_id = blogs.teacher_id)
					WHERE (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
					AND classes.active = :status
					GROUP BY classes.class_id, class_name, classes.class_cat_id, classes.teacher_id, classes.active, class_cat_name,
					teacher_name, subjects, num_students, blog_id, blog_name
					ORDER BY classes.sort_order"); 
		
        $sth->execute( array(':teacherId' => $teacherId, ':status' => $status));
 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {		
			foreach( $results as $result)
			{
				$result->subjects = pg_array_parse($result->subjects);
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
			$app->response()->setStatus(404);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));        
    }

});

$app->get('/getAllClassExams/:class_id(/:exam_type_id/:teacher_id)', function ($classId, $examTypeId = null, $teacherId = null) {
    //Return all associated class subject exams, including the parent subjects
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$query = "SELECT class_sub_exam_id, class_subjects.class_subject_id, class_subjects.subject_id, subjects.teacher_id,
								subject_name, class_subject_exams.exam_type_id, exam_type, grade_weight, parent_subject_id,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null then false else true end as is_parent
							FROM app.class_subjects 
							LEFT JOIN app.class_subject_exams
								INNER JOIN app.exam_types
								ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
							WHERE class_subjects.class_id = :classId
							AND class_subjects.active is true
							";
		$params = array(':classId' => $classId);
		
		if( $examTypeId !== null && $examTypeId !== '0' )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId ";
			$params[':examTypeId'] = $examTypeId; 
		}
		
		if( $teacherId !== null && $teacherId !== '0' )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId)";
			$params[':teacherId'] = $teacherId; 
		}
		
		$query .= " ORDER BY exam_types.sort_order, subjects.sort_order";
		
        $sth = $db->prepare($query);
        $sth->execute( $params  );
 
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
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));        
    }

});

$app->get('/getClassExams/:class_id(/:exam_type_id)', function ($classId, $examTypeId = null) {
    //Returns only the class subject exams that the user will enter exam marks for
	// parent subjects are not returned, these exam mark totals are calculated based on their children subjects
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$query = "SELECT class_sub_exam_id, class_subjects.class_subject_id, class_subjects.subject_id, 
								subject_name, class_subject_exams.exam_type_id, exam_type, grade_weight, parent_subject_id,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id limit 1) as parent_subject_name
							FROM app.class_subjects 
							INNER JOIN app.class_subject_exams
								INNER JOIN app.exam_types
								ON class_subject_exams.exam_type_id = exam_types.exam_type_id
							ON class_subjects.class_subject_id = class_subject_exams.class_subject_id
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							WHERE class_id = :classId
							AND class_subjects.active is true
							AND (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id limit 1) is null 
							";
		$params = array(':classId' => $classId);
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId";
			$params[':examTypeId'] = $examTypeId; 
		}
		$query .= " ORDER BY exam_types.sort_order, subjects.sort_order";
		
        $sth = $db->prepare($query);
        $sth->execute( $params  );
 
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
			$app->response()->headers->set('Content-Type', 'application/json');
			echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));        
    }

});

$app->post('/addClass', function () use($app) {
    // Add class
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$className = 		( isset($allPostVars['class_name']) ? $allPostVars['class_name']: null);
	$classCatId = 		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId = 		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$reportCardType = 	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$subjects =  		( isset($allPostVars['subjects']) ? $allPostVars['subjects']: null);	
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
		
        /* get the next number for sort order */		
		$sth0 = $db->prepare("SELECT max(sort_order) as sort_order FROM app.classes WHERE active is true");	
			
        $sth = $db->prepare("INSERT INTO app.classes(class_name, class_cat_id, teacher_id, created_by, report_card_type, sort_order) 
            VALUES(:className, :classCatId, :teacherId, :userId, :reportCardType, :sortOrder)");
 		
		if( count($subjects) > 0 )
		{
			$sth2 = $db->prepare("INSERT INTO app.class_subjects(class_id, subject_id, created_by)
									VALUES(currval('app.classes_class_id_seq'), :subjectId, :userId)");
			
			$sth3 = $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
									VALUES(currval('app.class_subjects_class_subject_id_seq'), :examTypeId, :gradeWeight, :userId)");
		}
		
		$db->beginTransaction();
		
		$sth0->execute();
		$sort = $sth0->fetch(PDO::FETCH_OBJ);
		$sortOrder = ($sort && $sort->sort_order !== NULL ? $sort->sort_order + 1 : 1);
		
		$sth->execute( array(':className' => $className, ':classCatId' => $classCatId, ':teacherId' => $teacherId, ':userId' => $userId, ':reportCardType' => $reportCardType, ':sortOrder' => $sortOrder ) );
		
		if( count($subjects) > 0 )
		{
			foreach( $subjects as $subject )
			{
				$subjectId = ( isset($subject['subject_id']) ? $subject['subject_id']: null);
				$sth2->execute( array(':subjectId' => $subjectId, ':userId' => $userId ) );
				
				if( count($subject['exams']) > 0 )
				{
					foreach( $subject['exams'] as $exam )
					{
						$examTypeId = ( isset($exam['exam_type_id']) ? $exam['exam_type_id']: null);
						$gradeWeight = ( isset($exam['grade_weight']) ? $exam['grade_weight']: null);
						
						$sth3->execute( array(':examTypeId' => $examTypeId, ':gradeWeight' => $gradeWeight, ':userId' => $userId ) );
					}
				}
				
			}
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

$app->put('/updateClass', function () use($app) {
    // Update class
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classId = 			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$className = 		( isset($allPostVars['class_name']) ? $allPostVars['class_name']: null);
	$classCatId = 		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId = 		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$reportCardType = 	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$subjects =  		( isset($allPostVars['subjects']) ? $allPostVars['subjects']: null);	
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $updateClass = $db->prepare("UPDATE app.classes
			SET class_name = :className,
				class_cat_id = :classCatId,
				teacher_id = :teacherId,
				report_card_type = :reportCardType,
				active = true,
				modified_date = now(),
				modified_by = :userId
            WHERE class_id = :classId");
			
		
		$inactivateSubject =  $db->prepare("UPDATE app.class_subjects 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
		
		$inactivateAllSubjectExams = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
								
					
		if( count($subjects) > 0 )
		{
			$insertSubject =  $db->prepare("INSERT INTO app.class_subjects(class_id,subject_id,created_by)
							VALUES(:classId,:subjectId,:userId)");
		
			$updateExam =  $db->prepare("UPDATE app.class_subject_exams 
							SET grade_weight = :gradeWeight,
								active = true,
								modified_date =  now(),
								modified_by = :userId
							WHERE class_sub_exam_id = :classSubExamId");			
			
			$insertNewSubjectExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(currval('app.class_subjects_class_subject_id_seq'), :examTypeId, :gradeWeight, :userId)");
							
			$insertExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(:classSubjectId, :examTypeId, :gradeWeight, :userId)");
			
			$inactivateExam = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_sub_exam_id = :classSubExamId");
			
			
			$activateSubject =  $db->prepare("UPDATE app.class_subjects 
								SET active = true,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
		
								
		}
		
		// pull out existing class subjects	    
	    $query = $db->prepare("SELECT class_subject_id, class_id, subject_id FROM app.class_subjects WHERE class_id = :classId");
		$query->execute( array(':classId' => $classId ) );
		$currentSubjects = $query->fetchAll(PDO::FETCH_OBJ);
		
		// pull out existing class subject exams  
	    $query = $db->prepare("SELECT class_sub_exam_id, class_subject_exams.class_subject_id, subject_id, exam_type_id 
								FROM app.class_subject_exams INNER JOIN app.class_subjects USING (class_subject_id) WHERE class_id = :classId");
		$query->execute( array(':classId' => $classId ) );
		$currentSubjectExams = $query->fetchAll(PDO::FETCH_OBJ);
						
		$db->beginTransaction();
		
        $updateClass->execute( array(':className' => $className, 
									 ':classCatId' => $classCatId, 
									 ':teacherId' => $teacherId, 
									 ':reportCardType' => $reportCardType,
									 ':userId' => $userId,
									 ':classId' => $classId) ); 

		if( count($subjects) > 0 )
		{
			foreach($subjects as $key => $subject)
			{
				$classSubjectId = ( isset($subject['class_subject_id']) ? $subject['class_subject_id']: null);
				$subjectId = ( isset($subject['subject_id']) ? $subject['subject_id']: null);
				$exams =	( isset($subject['exams']) ? $subject['exams']: null);
				
				/* no class subject was passed in, so it's either a new subject added or activating an existing inactive one */
				if( $classSubjectId === null )
				{
					$updated = false;
					
					// if already there but inactive, set to active
					foreach( $currentSubjects as $currentSubject )
					{
						if( $currentSubject->class_id == $classId && $currentSubject->subject_id == $subjectId )
						{
							$activateSubject->execute(array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId));
							$subjects[$key]['class_subject_id'] = $currentSubject->class_subject_id;
							// set class subject id in all associated exams
							foreach($exams as $key2 => $exam)
							{
								$exams[$key2]['class_subject_id'] = $currentSubject->class_subject_id;
							}
							$updated = true;
							break;
						}
					}
					
					// else add
					if( !$updated )
					{
						$insertSubject->execute( array(':classId' => $classId,
												':subjectId' => $subjectId, 					 
												':userId' => $userId
						) );
					}
				}
				
				// check exams?
				if( count($exams) > 0 )
				{
					foreach($exams as $key => $exam)
					{
						$examTypeId =		( isset($exam['exam_type_id']) ? $exam['exam_type_id']: null);
						$gradeWeight =		( isset($exam['grade_weight']) ? $exam['grade_weight']: null);
						$classSubExamId =	( isset($exam['class_sub_exam_id']) ? $exam['class_sub_exam_id']: null);
						$classSubjectId =	( isset($exam['class_subject_id']) ? $exam['class_subject_id']: null);
						
						if( $classSubExamId !== null )			
						{
							$updateExam->execute(array(':gradeWeight' => $gradeWeight, ':classSubExamId' => $classSubExamId, ':userId' => $userId));
						}
						else
						{
							// if no class subject id, then subject was new, use new seq value
							
							$examUpdated = false;
								
							// if already there but inactive, set to active
							foreach( $currentSubjectExams as $currentSubjectExam )
							{
								if( $currentSubjectExam->subject_id == $subjectId && $currentSubjectExam->exam_type_id == $examTypeId )
								{
									$updateExam->execute(array(':gradeWeight' => $gradeWeight, ':classSubExamId' => $currentSubjectExam->class_sub_exam_id, ':userId' => $userId));
									$exams[$key]['class_sub_exam_id'] = $currentSubjectExam->class_sub_exam_id;
									$examUpdated = true;
									break;
								}
							}
							
							// else add
							if( !$examUpdated )
							{	
								if( $classSubjectId === null )			
								{	
									$insertNewSubjectExam->execute( array(':examTypeId' => $examTypeId,		
													':gradeWeight' => $gradeWeight,
													':userId' => $userId
									) );	
								}
								else
								{
									$insertExam->execute( array(':classSubjectId' => $classSubjectId, 	
															':examTypeId' => $examTypeId,		
															':gradeWeight' => $gradeWeight,
															':userId' => $userId
									) );
								}																			
							}	
						}
					}
					
				}
				
			}
		    
			// set active to false for any not passed in			
			foreach( $currentSubjects as $currentSubject )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $subjects as $subject )
				{
					if( isset($subject['class_subject_id']) && $subject['class_subject_id'] == $currentSubject->class_subject_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$inactivateSubject->execute(array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId));
					
					// if subject was marked inactive, mark exams inactive as well
					$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
					
				}
			}
			
			// set active to false for any not passed in					
			foreach( $currentSubjectExams as $currentSubjectExam )
			{	
				$deleteMe = true;
				
				foreach( $subjects as $subject )
				{
					// if found, do not delete
					foreach( $subject['exams'] as $exam )
					{						
						if( isset($exam['class_sub_exam_id']) && $exam['class_sub_exam_id'] == $currentSubjectExam->class_sub_exam_id )
						{
							$deleteMe = false;
						}
					}
				}
				if( $deleteMe )
				{
					$inactivateExam->execute(array(':classSubExamId' => $currentSubjectExam->class_sub_exam_id, ':userId' => $userId));								
				}
			}
			
			
		}
		else
		{
			// no subjects, remove any associated with class
			// if subject was marked inactive, mark exams inactive as well
			foreach( $currentSubjects as $currentSubject )
			{	
				$inactivateSubject->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
				$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) ); 
			}

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

$app->put('/updateTeacherSubject', function () use($app) {
    // Update class, but only update teachers subject
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classId = 	( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$teacherId =( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$subjects = ( isset($allPostVars['subjects']) ? $allPostVars['subjects']: null);	
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
		
		$inactivateSubject =  $db->prepare("UPDATE app.class_subjects 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
		
		$inactivateAllSubjectExams = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
								
					
		if( count($subjects) > 0 )
		{
			$insertSubject =  $db->prepare("INSERT INTO app.class_subjects(class_id,subject_id,created_by)
							VALUES(:classId,:subjectId,:userId)");
							
			$updateExam =  $db->prepare("UPDATE app.class_subject_exams 
							SET grade_weight = :gradeWeight,
								active = true,
								modified_date =  now(),
								modified_by = :userId
							WHERE class_sub_exam_id = :classSubExamId");			
			
			$insertNewSubjectExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(currval('app.class_subjects_class_subject_id_seq'), :examTypeId, :gradeWeight, :userId)");

							
			$insertExam =  $db->prepare("INSERT INTO app.class_subject_exams(class_subject_id, exam_type_id, grade_weight, created_by)
							VALUES(:classSubjectId, :examTypeId, :gradeWeight, :userId)");
			
			$inactivateExam = $db->prepare( "UPDATE app.class_subject_exams 
								SET active = false,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_sub_exam_id = :classSubExamId");
			
			
			$activateSubject =  $db->prepare("UPDATE app.class_subjects 
								SET active = true,
									modified_date =  now(),
									modified_by = :userId
								WHERE class_subject_id = :classSubjectId");
		
								
		}
		
		// pull out existing class subjects	    
	    $query = $db->prepare("SELECT class_subject_id, class_id, class_subjects.subject_id 
								FROM app.class_subjects 
								INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
								WHERE class_id = :classId
								AND subjects.teacher_id = :teacherId");
		$query->execute( array(':classId' => $classId, ':teacherId' => $teacherId ) );
		$currentSubjects = $query->fetchAll(PDO::FETCH_OBJ);
		
		// pull out existing class subject exams  
	    $query = $db->prepare("SELECT class_sub_exam_id, class_subject_exams.class_subject_id, class_subjects.subject_id, exam_type_id 
								FROM app.class_subject_exams 
								INNER JOIN app.class_subjects 
									INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id
								ON class_subject_exams.class_subject_id = class_subjects.class_subject_id 
								WHERE class_id = :classId
								AND subjects.teacher_id = :teacherId");
		$query->execute( array(':classId' => $classId, ':teacherId' => $teacherId ) );
		$currentSubjectExams = $query->fetchAll(PDO::FETCH_OBJ);
					
		$db->beginTransaction();
		
		if( count($subjects) > 0 )
		{
			
			foreach($subjects as $key => $subject)
			{
				$classSubjectId = ( isset($subject['class_subject_id']) ? $subject['class_subject_id']: null);
				$subjectId = ( isset($subject['subject_id']) ? $subject['subject_id']: null);
				$exams =	( isset($subject['exams']) ? $subject['exams']: null);
				
				// no class subject was passed in, so it's either a new subject added or activating an existing inactive one 
				if( $classSubjectId === null )
				{
					$subjectUpdated = false;
						
						
					// if already there but inactive, set to active

					foreach( $currentSubjects as $currentSubject )
					{
						if( $currentSubject->class_id == $classId && $currentSubject->subject_id == $subjectId )
						{
							$activateSubject->execute(array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId));
							$subjects[$key]['class_subject_id'] = $currentSubject->class_subject_id;
							// set class subject id in all associated exams
							foreach($exams as $key2 => $exam)
							{
								$exams[$key2]['class_subject_id'] = $currentSubject->class_subject_id;
							}
							$subjectUpdated = true;
							break;
						}
					}
					
					// else add
					if( !$subjectUpdated )
					{
						$insertSubject->execute( array(':classId' => $classId,
												':subjectId' => $subjectId, 					 
												':userId' => $userId
						) );
					}
					
				}
				
				// check exams?
				if( count($exams) > 0 )
				{
					foreach($exams as $key => $exam)
					{
						$examTypeId =		( isset($exam['exam_type_id']) ? $exam['exam_type_id']: null);
						$gradeWeight =		( isset($exam['grade_weight']) ? $exam['grade_weight']: null);
						$classSubExamId =	( isset($exam['class_sub_exam_id']) ? $exam['class_sub_exam_id']: null);
						$classSubjectId =	( isset($exam['class_subject_id']) ? $exam['class_subject_id']: null);
						
						if( $classSubExamId !== null )			
						{
							$updateExam->execute(array(':gradeWeight' => $gradeWeight, ':classSubExamId' => $classSubExamId, ':userId' => $userId));
						}
						else
						{
							// if no class subject id, then subject was new, use new seq value
							
							$examUpdated = false;
								
							// if already there but inactive, set to active
							foreach( $currentSubjectExams as $currentSubjectExam )
							{
								if( $currentSubjectExam->subject_id == $subjectId && $currentSubjectExam->exam_type_id == $examTypeId )
								{
									$updateExam->execute(array(':gradeWeight' => $gradeWeight, ':classSubExamId' => $currentSubjectExam->class_sub_exam_id, ':userId' => $userId));
									$exams[$key]['class_sub_exam_id'] = $currentSubjectExam->class_sub_exam_id;
									$examUpdated = true;
									break;
								}
							}
							
							// else add
							if( !$examUpdated )
							{	
								if( $classSubjectId === null )			
								{	
									$insertNewSubjectExam->execute( array(':examTypeId' => $examTypeId,		
													':gradeWeight' => $gradeWeight,
													':userId' => $userId
									) );	
								}
								else
								{
									$insertExam->execute( array(':classSubjectId' => $classSubjectId, 	
															':examTypeId' => $examTypeId,		
															':gradeWeight' => $gradeWeight,
															':userId' => $userId
									) );
								}																			
							}	
						}
					}
										
				}
				
			}
			
		    
			// set active to false for any not passed in			
			foreach( $currentSubjects as $currentSubject )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $subjects as $subject )
				{
					if( isset($subject['class_subject_id']) && $subject['class_subject_id'] == $currentSubject->class_subject_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$inactivateSubject->execute(array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId));
					
					// if subject was marked inactive, mark exams inactive as well
					$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
					
				}
			}
			
			// set active to false for any not passed in					
			foreach( $currentSubjectExams as $currentSubjectExam )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $subjects as $subject )
				{
					foreach( $subject['exams'] as $exam )
					{
						if( isset($exam['class_sub_exam_id']) && $exam['class_sub_exam_id'] == $currentSubjectExam->class_sub_exam_id )
						{
							$deleteMe = false;
						}
					}
				}
				
				if( $deleteMe )
				{
					$inactivateExam->execute(array(':classSubExamId' => $currentSubjectExam->class_sub_exam_id, ':userId' => $userId));								
				}
			}
			
			
		}
		else
		{
			// no subjects, remove any associated with class
			// if subject was marked inactive, mark exams inactive as well
			foreach( $currentSubjects as $currentSubject )
			{	
				$inactivateSubject->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) );
				$inactivateAllSubjectExams->execute( array(':classSubjectId' => $currentSubject->class_subject_id, ':userId' => $userId) ); 
			}

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

$app->put('/setClassStatus', function () use($app) {
    // Update class status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classId =	( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.classes
							SET active = :status,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_id = :classId
							"); 
        $sth->execute( array(':classId' => $classId, 
							 ':status' => $status, 
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

$app->put('/setClassSortOrder', function () use($app) {
    // Update class sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.classes
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_id = :classId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$classId =		( isset($item['class_id']) ? $item['class_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':classId' => $classId, 
							 ':sortOrder' => $sortOrder, 
							 ':userId' => $userId
					) );
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

$app->get('/checkClass/:class_id', function ($classId) {
    // Check is class can be deleted
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(exam_id) as num_exams
								FROM app.exam_marks
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.class_subjects
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE classes.class_id = :classId");
        $sth->execute( array(':classId' => $classId ) );
 
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

$app->delete('/deleteClass/:class_id', function ($classId) {
    // delete class
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$d1 = $db->prepare("DELETE FROM app.class_subject_exams 
								WHERE class_sub_exam_id in (select class_sub_exam_id 
															from app.class_subjects
															inner join app.class_subject_exams
															on class_subjects.class_subject_id = class_subject_exams.class_subject_id
															where class_id = :classId)
							");
		$d2 = $db->prepare("DELETE FROM app.class_subjects WHERE class_id = :classId");		
		
		$d3 = $db->prepare("DELETE FROM app.classes WHERE class_id = :classId");	
										
		$db->beginTransaction();
		$d1->execute( array(':classId' => $classId) );
		$d2->execute( array(':classId' => $classId) );
		$d3->execute( array(':classId' => $classId) );
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

// ************** Class Categories  ****************** //
$app->get('/getClassCats(/:teacher_id)', function ($teacherId=null) {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		if( $teacherId !== null )
		{
			$sth = $db->prepare("SELECT class_cats.class_cat_id, class_cat_name 
								FROM app.class_cats 
								INNER JOIN app.classes 
									INNER JOIN app.class_subjects
									INNER JOIN app.subjects
									ON class_subjects.subject_id = subjects.subject_id
								ON classes.class_id = class_subjects.class_id
								ON class_cats.class_cat_id = classes.class_cat_id
								WHERE class_cats.active is true 
								AND (classes.teacher_id = :teacherId OR subjects.teacher_id = :teacherId)
								GROUP BY class_cats.class_cat_id, class_cat_name 
								ORDER BY class_cats.class_cat_id");
			$sth->execute(array(':teacherId' => $teacherId));
		}
		else
		{
			$sth = $db->prepare("SELECT class_cat_id, class_cat_name 
								FROM app.class_cats 
								WHERE active is true 
								ORDER BY class_cat_id");
			$sth->execute();
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
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/addClassCat', function () use($app) {
    // Add class category
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatName = ( isset($allPostVars['class_cat_name']) ? $allPostVars['class_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
        $sth1 = $db->prepare("INSERT INTO app.class_cats(class_cat_name,created_by) 
            VALUES(:classCatName,:userId)"); 
			
		$sth2 = $db->prepare("SELECT currval('app.class_cats_class_cat_id_seq') as class_cat_id");	
		
		$db->beginTransaction();
		$sth1->execute( array(':classCatName' => $classCatName, ':userId' => $userId) );
		$sth2->execute();
		$classCatId = $sth2->fetch(PDO::FETCH_OBJ);
		$db->commit();		
        
 
		$result = new stdClass();
		$result->class_cat_id = $classCatId->class_cat_id;
		$result->class_cat_name = $classCatName;
	
		if( $classCatId )
		{ 
			$app->response->setStatus(200);
			$app->response()->headers->set('Content-Type', 'application/json');
			echo json_encode(array("response" => "success", "data" => $result));
			$db = null;
		}
		else
		{
			$app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $e->getMessage() ));
            $db = null;
		}
 
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateClassCat', function () use($app) {
    // Update class cat
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatId = ( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$classCatName = ( isset($allPostVars['class_cat_name']) ? $allPostVars['class_cat_name']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.class_cats
							SET class_cat_name = :classCatName,
								modified_date = now(),
								modified_by = :userId 
							WHERE class_cat_id = :classCatId
							"); 
        $sth->execute( array(':classCatId' => $classCatId, 
							 ':classCatName' => $classCatName, 
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

$app->put('/setClassCatStatus', function () use($app) {
    // Update class cat status
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$classCatId = ( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

    try 
    {
       	$sth3 = $db->prepare("UPDATE app.subjects
						SET active = :status,
							modified_date = now(),
							modified_by = :userId 
						WHERE class_cat_id = :classCatId
						"); 
					
		$db->beginTransaction();
        $sth1->execute( array(':classCatId' => $classCatId, ':status' => $status,  ':userId' => $userId) );
		$sth2->execute( array(':classCatId' => $classCatId, ':status' => $status,  ':userId' => $userId) );
		$sth3->execute( array(':classCatId' => $classCatId, ':status' => $status,  ':userId' => $userId) );
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

$app->get('/getClassCatsSummary', function () {
    //Show all class categories
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT class_cat_id, class_cat_name,
							(select count(*) 
								from app.students 
								inner join app.classes 
								on students.current_class = classes.class_id
								where class_cat_id = class_cats.class_cat_id) as num_students
							FROM app.class_cats 
							WHERE active is true
							ORDER BY class_cat_id");
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
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/checkClassCat/:class_cat_id', function ($classCatId) {
    // Check is class category is associated with any exam marks
	
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();
        $sth = $db->prepare("SELECT count(exam_id) as num_exams
								FROM app.exam_marks
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.class_subjects
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE class_cat_id = :classCatId");
        $sth->execute( array(':classCatId' => $classCatId ) );
 
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

$app->delete('/deleteClassCat/:class_cat_id', function ($classCatId) {
    // delete class category
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$d1 = $db->prepare("DELETE FROM app.class_subject_exams 
								WHERE class_sub_exam_id in (select class_sub_exam_id 
															from app.classes
															inner join app.class_subjects
																inner join app.class_subject_exams
																on class_subjects.class_subject_id = class_subject_exams.class_subject_id
															on classes.class_id = class_subjects.class_id
															where class_cat_id = :classCatId)
							");
		$d2 = $db->prepare("DELETE FROM app.class_subjects 
								WHERE class_subject_id in (select class_subject_id 
															from app.classes
															inner join app.class_subjects
															on classes.class_id = class_subjects.class_id
															where class_cat_id = :classCatId)
							");
		$d3 = $db->prepare("DELETE FROM app.classes WHERE class_cat_id = :classCatId");
		
		
		$d4 = $db->prepare("DELETE FROM app.exam_types WHERE class_cat_id = :classCatId");		
		$d5 = $db->prepare("DELETE FROM app.subjects WHERE class_cat_id = :classCatId");		
		$d6 = $db->prepare("DELETE FROM app.class_cats WHERE class_cat_id = :classCatId");		
										
		$db->beginTransaction();
		$d1->execute( array(':classCatId' => $classCatId) );
		$d2->execute( array(':classCatId' => $classCatId) );
		$d3->execute( array(':classCatId' => $classCatId) );
		$d4->execute( array(':classCatId' => $classCatId) );
		$d5->execute( array(':classCatId' => $classCatId) );
		$d6->execute( array(':classCatId' => $classCatId) );
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
