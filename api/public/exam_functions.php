<?php
$app->get('/getExamTypes/:class_cat_id', function ($classCatId) {

    // Get all exam types
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
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

$app->post('/addExamType', function () use($app) {
    // Add exam type
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$examType =		( isset($allPostVars['exam_type']) ? $allPostVars['exam_type']: null);
	$classCatId =	( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();

		$sth0 = $db->prepare("SELECT max(sort_order) as sort_order FROM app.exam_types WHERE class_cat_id = :classCatId");
		/* get the next number for sort order */		
		$sth1 = $db->prepare("INSERT INTO app.exam_types(exam_type, class_cat_id, sort_order, created_by) 
								VALUES(:examType, :classCatId, :sortOrder, :userId)"); 
		$sth2 = $db->prepare("SELECT * FROM app.exam_types WHERE exam_type_id = currval('app.exam_types_exam_type_id_seq')");

						
		$db->beginTransaction();
		$sth0->execute( array(':classCatId' => $classCatId) );
		$sort = $sth0->fetch(PDO::FETCH_OBJ);
		$sortOrder = ($sort && $sort->sort_order !== NULL ? $sort->sort_order + 1 : 1);

		$sth1->execute( array(':examType' => $examType, ':classCatId' => $classCatId, ':sortOrder' => $sortOrder, ':userId' => $userId ) );
		$sth2->execute();
		$results = $sth2->fetch(PDO::FETCH_OBJ);

		$db->commit();
		$app->response->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo json_encode(array("response" => "success", "data" => $results));
		$db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->delete('/deleteExamType/:exam_type_id', function ($examTypeId) {
    // delete exam type
	
	$app = \Slim\Slim::getInstance();

    try 
    {
        $db = getDB();

		$sth = $db->prepare("DELETE FROM app.exam_types WHERE exam_type_id = :examTypeId");		
										
		$sth->execute( array(':examTypeId' => $examTypeId) );
 
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

$app->put('/setExamTypeSortOrder', function () use($app) {
    // Update exam type sort order
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

    try 
    {
        $db = getDB();
        $sth = $db->prepare("UPDATE app.exam_types
							SET sort_order = :sortOrder,
								modified_date = now(),
								modified_by = :userId 
							WHERE exam_type_id = :examTypeId
							"); 
							
		$db->beginTransaction();
		foreach( $sortData as $item )
		{
			$examTypeId =	( isset($item['exam_type_id']) ? $item['exam_type_id']: null);
			$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
			$sth->execute( array(':examTypeId' => $examTypeId, 
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

$app->get('/getStudentExamMarks/:student_id/:class/:term(/:type)', function ($studentId,$classId,$termId,$examTypeId=null) {
    //Get student exam marks
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		
		// get exam marks by exam type
		$queryArray = array(':studentId' => $studentId, ':classId' => $classId, ':termId' => $termId);
		$query = "SELECT subject_name, parent_subject_name, mark, grade_weight, exam_type, rank, grade
					FROM (
						SELECT class_id
							  ,subject_name  
							  ,(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
							  ,exam_type
							  ,student_id
							  ,mark          
							  ,grade_weight
							  ,(select grade from app.grading where (mark::float/grade_weight::float)*100 between min_mark and max_mark) as grade
							  ,dense_rank() over w as rank,
							  subjects.sort_order,
							  exam_types.exam_type_id
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
						";
						
		if( $examTypeId !== null )
		{
			$query .= "AND class_subject_exams.exam_type_id = :examTypeId ";
								
			$queryArray[':examTypeId'] = $examTypeId; 
		}
		
		$query .= "WINDOW w AS (PARTITION BY class_subjects.subject_id, class_subject_exams.exam_type_id ORDER BY  subjects.sort_order, exam_types.exam_type_id)
				 ) q
				 where student_id = :studentId
				 ORDER BY sort_order,exam_type_id ";
		
		$sth = $db->prepare($query);
		$sth->execute( $queryArray ); 
        
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

$app->get('/getClassExamMarks/:class_id/:term_id/:exam_type_id(/:teacher_id)', function ($classId, $termId, $examTypeId, $teacherId=null) {
    //Show exam marks for all students in class
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array(':classId' => $classId, ':termId' => $termId, ':examTypeId' => $examTypeId);
		$query = "SELECT q.student_id, student_name, subject_name, q.class_sub_exam_id, mark, exam_marks.term_id, grade_weight, sort_order, parent_subject_id, subject_id, is_parent
							FROM (
								SELECT  students.student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name, 
									subject_name, class_subject_exams.class_sub_exam_id, grade_weight, subjects.sort_order, parent_subject_id, subjects.subject_id,
									case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is null then false else true end as is_parent
								FROM app.students 
								INNER JOIN app.classes 
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										INNER JOIN app.class_subject_exams																	
										ON class_subjects.class_subject_id = class_subject_exams.class_subject_id		
									ON classes.class_id = class_subjects.class_id									
								ON students.current_class = classes.class_id					
								WHERE current_class = :classId
								AND exam_type_id = :examTypeId							
							";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId) ";
			$params[':teacherId'] = $teacherId;
		}
		
		$query .= ") q
							LEFT JOIN app.exam_marks 
								INNER JOIN app.terms 
								ON exam_marks.term_id = terms.term_id															
							ON q.class_sub_exam_id = exam_marks.class_sub_exam_id AND exam_marks.term_id = :termId AND exam_marks.student_id = q.student_id
							ORDER BY student_name, sort_order, subject_name";
		
        $sth = $db->prepare($query);
        $sth->execute($params); 
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

$app->get('/getAllStudentExamMarks/:class/:term/:type(/:teacherId)', function ($classId,$termId,$examTypeId,$teacherId=null) {
    //Get all student exam marks
	$app = \Slim\Slim::getInstance();
	
    try 
    {
		// need to make sure class, term and type are integers
		if( is_numeric($classId) && is_numeric($termId)  && is_numeric($examTypeId) )
		{
			$db = getDB();
			
			$query = "select app.colpivot('_exam_marks', 'SELECT first_name || '' '' || coalesce(middle_name,'''') || '' '' || last_name as student_name
							 ,classes.class_id
							  ,subject_name     
							  ,coalesce((select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1),'''') as parent_subject_name
							  ,exam_type
							  ,exam_marks.student_id
							  ,mark          
							  ,grade_weight
							  ,subjects.sort_order
						FROM app.exam_marks
						INNER JOIN app.class_subject_exams 
						INNER JOIN app.exam_types
						ON class_subject_exams.exam_type_id = exam_types.exam_type_id
						INNER JOIN app.class_subjects 
							INNER JOIN app.subjects
							ON class_subjects.subject_id = subjects.subject_id
							INNER JOIN app.classes
							ON class_subjects.class_id = classes.class_id
						ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
						ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
						INNER JOIN app.students ON exam_marks.student_id = students.student_id
						WHERE class_subjects.class_id = $classId
						AND term_id = $termId						
						AND class_subject_exams.exam_type_id = $examTypeId
						";
		if( $teacherId !== null )
		{
			$query .= "AND (subjects.teacher_id = $teacherId OR classes.teacher_id = $teacherId) ";
		}
		
		$query .= "	WINDOW w AS (PARTITION BY class_subject_exams.exam_type_id, class_subjects.subject_id ORDER BY subjects.sort_order, mark desc)
						',
						array['student_id','student_name','exam_type'], array['sort_order','parent_subject_name','subject_name','grade_weight'], '#.mark', null);";
			
			$query2 = "select *,
									(
										SELECT rank FROM (
											SELECT
												student_id,
												total_mark,
												dense_rank() over w as rank
											FROM (
												SELECT student_id, 
													coalesce(sum(case when subjects.parent_subject_id is null then
														mark
													end),0) as total_mark
												FROM app.exam_marks
												INNER JOIN app.class_subject_exams 
													INNER JOIN app.exam_types
													ON class_subject_exams.exam_type_id = exam_types.exam_type_id
													INNER JOIN app.class_subjects 
														INNER JOIN app.subjects
														ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
													ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
												ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
												WHERE class_subjects.class_id = $classId
												AND term_id = $termId
												AND class_subject_exams.exam_type_id = $examTypeId
												GROUP BY exam_marks.student_id
											) a
											WINDOW w AS (ORDER BY total_mark desc)	
										) q
										WHERE student_id = _exam_marks.student_id
									) as rank
									from _exam_marks order by exam_type, rank;";

			$sth1 = $db->prepare($query);
			$sth2 = $db->prepare($query2);
			
			$db->beginTransaction();
			$sth1->execute(); 
			$sth2->execute();
			$results = $sth2->fetchAll(PDO::FETCH_OBJ);
			$db->commit();
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
        //echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
		 echo json_encode(array('response' => 'success', 'nodata' => 'No students found' ));
    }

});

$app->post('/addExamMarks', function () use($app) {
    // Add exam type
	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$examMarks =	( isset($allPostVars['exam_marks']) ? $allPostVars['exam_marks']: null);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	
    try 
    {
        $db = getDB();
		
		$getExam = $db->prepare("SELECT exam_id FROM app.exam_marks WHERE student_id = :studentId AND class_sub_exam_id = :classSubExamId AND term_id = :termId");
		
		$addMark = $db->prepare("INSERT INTO app.exam_marks(student_id, class_sub_exam_id, term_id, mark, created_by) 
								VALUES(:studentId, :classSubExamId, :termId, :mark, :userId)"); 
		
		$updateMark = $db->prepare("UPDATE app.exam_marks
									SET mark = :mark,
										modified_date = now(),
										modified_by = :userId
									WHERE exam_id = :examId"); 
									
		$deleteMark = $db->prepare("DELETE FROM app.exam_marks WHERE exam_id = :examId");
		
		$db->beginTransaction();
		
		if( count($examMarks) > 0 )
		{
			foreach($examMarks as $mark)
			{
				$studentId = ( isset($mark['student_id']) ? $mark['student_id']: null);				
				$classSubExamId = ( isset($mark['class_sub_exam_id']) ? $mark['class_sub_exam_id']: null);
				$termId = ( isset($mark['term_id']) ? $mark['term_id']: null);
				$mark = ( isset($mark['mark']) && !empty($mark['mark']) ? $mark['mark']: null);
				
				$currentExamMarks = $getExam->execute(array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId ));
				$examId = $getExam->fetch(PDO::FETCH_OBJ);
				
				if( $examId )
				{
					$updateMark->execute( array(':mark' => $mark, ':examId' => $examId->exam_id, ':userId' => $userId  ) );
				}
				else
				{
					$addMark->execute( array(':studentId' => $studentId, ':classSubExamId' => $classSubExamId, ':termId' => $termId, ':mark' => $mark, ':userId' => $userId ) );
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

$app->get('/getTopStudents(/:class_id)', function ($classId=null) {
    //Get all top 3 students for each class, or requested class
	
	$app = \Slim\Slim::getInstance();
	
    try 
    {
		
		$db = getDB();
		$queryParams = array();
		$query = "SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							total_mark, total_grade_weight, rank, percentage, 
								(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
								position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark    
									  ,total_grade_weight
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,dense_rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT    
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight									  
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true 
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
					";
			
		if( $classId !== null  )
		{
			$query .= " AND class_subjects.class_id = :classId ";
			$queryParams = array(':classId' => $classId );
		}
		
		$query .= " 	GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)								
							 ) q
							 WHERE rank < 4";
		
		//echo $query;

		$sth = $db->prepare($query);
		$sth->execute($queryParams); 
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

$app->get('/getTeacherTopStudents/:teacher_id(/:class_id)', function ($teacherId, $classId=null) {
    //Get all top 3 students for each class, or requested class
	
	$app = \Slim\Slim::getInstance();
	
    try 
    {
		
		$db = getDB();
		$queryParams = array(':teacherId' => $teacherId);
		$query = "SELECT student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name, class_id, class_name,
							total_mark, total_grade_weight, rank, percentage, 
								(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade,
								position_out_of
							FROM (
							SELECT
								 student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_id
									  ,class_name
									  ,total_mark    
									  ,total_grade_weight
									  ,round((total_mark/total_grade_weight)*100) as percentage
									  ,dense_rank() over w as rank
									  ,position_out_of
							FROM (
								SELECT    
									 exam_marks.student_id
									  ,first_name
									  ,middle_name
									  ,last_name
									  ,class_subjects.class_id
									  ,class_name
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										mark
										end),0) as total_mark
									  ,coalesce(sum(case when subjects.parent_subject_id is null then
										grade_weight
									   end),0) as total_grade_weight									  
									  ,(select count(*) from app.students where active is true and current_class = students.current_class) as position_out_of
								FROM app.exam_marks
								INNER JOIN app.students
								ON exam_marks.student_id = students.student_id
								INNER JOIN app.class_subject_exams 
									INNER JOIN app.exam_types
									ON class_subject_exams.exam_type_id = exam_types.exam_type_id
									INNER JOIN app.class_subjects 
										INNER JOIN app.subjects
										ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id AND classes.active is true 
									ON class_subject_exams.class_subject_id = class_subjects.class_subject_id
								ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
								WHERE term_id = (select term_id from app.current_term)
								AND (subjects.teacher_id = :teacherId OR classes.teacher_id = :teacherId)
					";
			
		if( $classId !== null  )
		{
			$query .= " AND class_subjects.class_id = :classId ";
			$queryParams[':classId'] = $classId;
		}
		
		$query .= " 	GROUP BY exam_marks.student_id, first_name, middle_name, last_name, class_subjects.class_id, class_name
					) a
								WINDOW w AS (PARTITION BY class_id ORDER BY class_id desc, total_mark desc)								
							 ) q
							 WHERE rank < 4";
		
		//echo $query;

		$sth = $db->prepare($query);
		$sth->execute($queryParams); 
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
?>
