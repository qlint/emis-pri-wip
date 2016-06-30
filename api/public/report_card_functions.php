<?php
$app->get('/getAllStudentReportCards/:class_id', function ($classId) {
    //Get report cards for class
	
	$app = \Slim\Slim::getInstance();
	
    try 
    {

		$db = getDB();
		
		$sth = $db->prepare("SELECT report_cards.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, 
									admission_number, report_cards.class_id, class_cat_id,
									class_name, report_cards.term_id, term_name, date_part('year', start_date) as year, report_data, report_cards.report_card_type,
									report_cards.teacher_id, employees.first_name || ' ' || coalesce(employees.middle_name,'') || ' ' || employees.last_name as teacher_name,
									report_cards.creation_date::date as date									
							FROM app.report_cards
							INNER JOIN app.students ON report_cards.student_id = students.student_id
							INNER JOIN app.classes ON report_cards.class_id = classes.class_id
							INNER JOIN app.terms ON report_cards.term_id = terms.term_id
							LEFT JOIN app.employees ON report_cards.teacher_id = employees.emp_id
							WHERE report_cards.class_id = :classId
							ORDER BY students.student_id");
		
		$sth->execute( array($classId) ); 
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
        //echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		$app->response()->headers->set('Content-Type', 'application/json');
		 echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
    }

});

$app->get('/getStudentReportCards/:student_id', function ($studentId) {
    //Get student report cards
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
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

$app->get('/getStudentReportCard/:student_id/:class_id/:term_id', function ($studentId, $classId, $termId) {
    //Get student report card
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();

		$sth = $db->prepare("SELECT report_card_id, report_cards.student_id, class_name, classes.class_id, term_name, report_cards.term_id, 
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

$app->get('/getExamMarksforReportCard/:student_id/:class/:term(/:teacherId)', function ($studentId,$classId,$termId,$teacherId=null) {
    //Get student exam marks
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get exam marks by exam type
		$params = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT subject_name, mark, grade_weight, exam_type, rank, grade, parent_subject_name, teacher_id
					FROM (
						SELECT class_subjects.class_id
							  ,subject_name      
							  ,exam_type
							  ,student_id
							  ,mark          
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
							  ,dense_rank() over w as rank,
							  subjects.sort_order,
							  exam_types.exam_type_id,
							  (select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
							  subjects.teacher_id
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id  AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						WHERE class_subjects.class_id = :classId
						AND term_id = :termId
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
			$params[':teacherId'] = $teacherId;
		}
		
		
		$query .= "	WINDOW w AS (PARTITION BY class_subjects.subject_id, class_subject_exams.exam_type_id ORDER BY subjects.sort_order, exam_types.exam_type_id)
				 ) q
				 where student_id = :studentId
				 ORDER BY sort_order, exam_type_id";
				 
				 
		$sth = $db->prepare($query);
		$sth->execute( $params ); 
		$details = $sth->fetchAll(PDO::FETCH_OBJ);
		

		// get overall marks per subjects, only use parent subjects
		$sth2 = $db->prepare("SELECT subject_name, total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade
							FROM (
							SELECT
								class_id, subject_id
										  ,subject_name      
										  ,student_id
										  ,total_mark    
										  ,total_grade_weight
										  ,round(total_mark::float/total_grade_weight::float*100) as percentage
										  ,dense_rank() over w as rank
										  ,sort_order
							FROM (
									SELECT class_id
									      ,class_subjects.subject_id
										  ,subject_name      
										  ,student_id
										  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
										  ,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight										  
										  ,subjects.sort_order
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
									AND subjects.parent_subject_id is null
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order
								) a
								WINDOW w AS (PARTITION BY subject_id ORDER BY sort_order, total_mark desc)								
							 ) q
							 where student_id = :studentId
							 ORDER BY sort_order");
		$sth2->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$subjectOverall = $sth2->fetchAll(PDO::FETCH_OBJ);
		
		// get overall position
		$sth3 = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight, 
										round(total_mark::float/total_grade_weight::float*100) as percentage,
										dense_rank() over w as rank, position_out_of
									FROM (
										SELECT    
											  student_id
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  , coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight											  
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
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
										AND subjects.parent_subject_id is null
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)									
								 ) q
								 where student_id = :studentId");
		$sth3->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$overall = $sth3->fetch(PDO::FETCH_OBJ);
		
		// get overall position last term
		$sth4 = $db->prepare("SELECT total_mark, total_grade_weight, rank, percentage, 
									(select grade from app.grading where percentage >= min_mark and  percentage <= max_mark) as grade,
									position_out_of
								FROM (
									SELECT
										student_id, total_mark, total_grade_weight, 
										round(total_mark::float/total_grade_weight::float*100) as percentage,
										dense_rank() over w as rank, position_out_of
									FROM (
										SELECT    
											  student_id
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
											  ,coalesce(sum(case when subjects.parent_subject_id is null then
														grade_weight
													end),0) as total_grade_weight											  
											  ,(select count(*) from app.students where active is true and current_class = :classId) as position_out_of
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
										AND term_id = (select term_id from app.terms where start_date < (select start_date from app.terms where term_id = :termId) order by start_date desc limit 1 )
										AND subjects.parent_subject_id is null
										GROUP BY exam_marks.student_id
									) a
									WINDOW w AS (ORDER BY coalesce(total_mark,0) desc)									
								 ) q
								 where student_id = :studentId");
		$sth4->execute(  array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId) ); 
		$overallLastTerm = $sth4->fetch(PDO::FETCH_OBJ);
		
		$results =  new stdClass();
		$results->details = $details;
		$results->subjectOverall = $subjectOverall;
		$results->overall = $overall;
		$results->overallLastTerm = $overallLastTerm;
        
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

$app->post('/addReportCard', function () use($app) {
    // Add report card
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$studentId =		( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
	$termId =			( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$classId =			( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$reportCardType =	( isset($allPostVars['report_card_type']) ? $allPostVars['report_card_type']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$reportData =		( isset($allPostVars['report_data']) ? $allPostVars['report_data']: null);
	
    try 
    {
        $db = getDB();
		
		$getReport = $db->prepare("SELECT report_card_id FROM app.report_cards WHERE student_id = :studentId AND class_id = :classId AND term_id = :termId");
		
		$addReport = $db->prepare("INSERT INTO app.report_cards(student_id, class_id, term_id, report_data, created_by, report_card_type, teacher_id) 
								VALUES(:studentId, :classId, :termId, :reportData, :userId, :reportCardType, :teacherId)"); 
		
		$updateReport = $db->prepare("UPDATE app.report_cards
									SET report_data = :reportData,
										modified_date = now(),
										modified_by = :userId
									WHERE report_card_id = :reportCardId"); 
									
		
		$db->beginTransaction();
		
		$getReport->execute( array(':studentId' => $studentId, ':classId' => $classId,':termId' => $termId ) );
		$reportCardId = $getReport->fetch(PDO::FETCH_OBJ);
		
		if( $reportCardId )
		{
			$updateReport->execute( array(':reportData' => $reportData, ':userId' => $userId, ':reportCardId' => $reportCardId->report_card_id ) );
		}
		else
		{
			$addReport->execute( array(':studentId' => $studentId, 
											':classId' => $classId,
											':reportCardType' => $reportCardType,
											':termId' => $termId, 
											':reportData' => $reportData, 
											':teacherId' => $teacherId,
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

$app->delete('/deleteReportCard/:report_card_id', function ($reportCardId) {
    // delete report card
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.report_cards WHERE report_card_id = :reportCardId");		
										
		$sth->execute( array(':reportCardId' => $reportCardId) );
 
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
