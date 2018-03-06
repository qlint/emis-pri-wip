<?php
$app->get('/getAllSubjects/:classCatId(/:status/:teacher_id)', function ($classCatId, $status = true, $teacherId = null) {
	//Show all subjects, including parent subjects

	$app = \Slim\Slim::getInstance();

	try
	{

	$db = getDB();
	if( $status === 'all' )
	{
		$query = "SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
								array_to_string(array_agg(distinct classes.class_name), ',') as class_name,
								subjects.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active, use_for_grading,
								parent_subject_id, subjects.sort_order,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is not null then true else false end as has_children
							FROM app.subjects
							LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
							INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							INNER JOIN app.classes ON class_cats.class_cat_id = classes.class_cat_id
							WHERE subjects.class_cat_id = :classCatId
							GROUP BY subject_id, class_cats.class_cat_name, employees.first_name, employees.middle_name, employees.last_name
							ORDER BY class_cat_name, sort_order, subject_name";
		$params = array(':classCatId' => $classCatId);
	}
	else
	{
		$params = array(':classCatId' => $classCatId, ':status' => $status);
		$query = "SELECT subjects.subject_id, subject_name, subjects.class_cat_id, class_cat_name,
								array_to_string(array_agg(distinct classes.class_name), ',') as class_name,
								subjects.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active, use_for_grading,
								parent_subject_id, subjects.sort_order,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is not null then true else false end as has_children
							FROM app.subjects
							LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
							INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id
							INNER JOIN app.classes ON class_cats.class_cat_id = classes.class_cat_id
							WHERE subjects.class_cat_id = :classCatId
							AND subjects.active = :status
							GROUP BY subject_id, class_cats.class_cat_name, employees.first_name, employees.middle_name, employees.last_name
							";
		if( $teacherId !== '0' )
		{
			$query .= "AND subjects.teacher_id = :teacherId ";
			$params['teacherId'] = $teacherId;
		}
		$query .= " ORDER BY class_cat_name, sort_order, subject_name";

	}
	$sth = $db->prepare($query);
	$sth->execute($params);

			$results = $sth->fetchAll(PDO::FETCH_ASSOC);

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

$app->get('/getAllTeacherSubjects/:teacherId/:classCatId(/:status)', function ($teacherId, $classCatId, $status = true) {
	//Show all subjects, including parent subjects

	$app = \Slim\Slim::getInstance();

	try
	{
	$db = getDB();
	if( $status === 'all' )
	{
		$query = "SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
								array_to_string(array_agg(distinct classes.class_name), ',') as class_name,
								subjects.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active, use_for_grading,
								parent_subject_id, subjects.sort_order,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is not null then true else false end as has_children
							FROM app.subjects
							LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
							INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							INNER JOIN app.classes ON class_cats.class_cat_id = classes.class_cat_id
							WHERE subjects.class_cat_id = :classCatId
							AND subjects.teacher_id = :teacherId
							GROUP BY subject_id, class_cats.class_cat_name, employees.first_name, employees.middle_name, employees.last_name
							ORDER BY class_cat_name, sort_order, subject_name";
		$params = array(':teacherId' => $teacherId, ':classCatId' => $classCatId);
	}
	else
	{
		$query = "SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
								array_to_string(array_agg(distinct classes.class_name), ',') as class_name,
								subjects.teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active, use_for_grading,
								parent_subject_id, subjects.sort_order,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name,
								case when (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is not null then true else false end as has_children
							FROM app.subjects
							LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
							INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							INNER JOIN app.classes ON class_cats.class_cat_id = classes.class_cat_id
							WHERE subjects.class_cat_id = :classCatId
							AND subjects.active = :status
							AND subjects.teacher_id = :teacherId
							GROUP BY subject_id, class_cats.class_cat_name, employees.first_name, employees.middle_name, employees.last_name
							ORDER BY class_cat_name, sort_order, subject_name";
		$params = array(':teacherId' => $teacherId, ':classCatId' => $classCatId, ':status' => $status);
	}
	$sth = $db->prepare($query);
	$sth->execute($params);

			$results = $sth->fetchAll(PDO::FETCH_ASSOC);

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

/*
$app->get('/getSubjects/:classCatId', function ($classCatId) {
	//Show only subjects that receive exam marks (children subjects)

	$app = \Slim\Slim::getInstance();

	try
	{
	$db = getDB();
	$sth = $db->prepare("SELECT subject_id, subject_name, subjects.class_cat_id, class_cat_name,
								teacher_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, subjects.active,
								parent_subject_id, sort_order,
								(select subject_name from app.subjects s where s.subject_id = subjects.parent_subject_id and s.active is true limit 1) as parent_subject_name
							FROM app.subjects
							LEFT JOIN app.employees ON subjects.teacher_id = employees.emp_id
							INNER JOIN app.class_cats ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
							WHERE subjects.class_cat_id = :classCatId
							AND subjects.active is true
							AND (select subject_id from app.subjects s where s.parent_subject_id = subjects.subject_id and s.active is true limit 1) is null
							ORDER BY class_cat_name, sort_order, subject_name;
						");

	$sth->execute(array(':classCatId' => $classCatId));

			$results = $sth->fetchAll(PDO::FETCH_ASSOC);

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
*/
$app->post('/addSubject', function () use($app) {
	// Add subject

	$allPostVars = json_decode($app->request()->getBody(),true);

	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$classId =		( isset($allPostVars['class_id']) ? $allPostVars['class_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$forGrading =			( isset($allPostVars['use_for_grading']) ? $allPostVars['use_for_grading']: false);

	try
	{
			$db = getDB();

	/* need to determine sort order, grab the last sort order number */

	$sortOrder = $db->prepare("SELECT max(sort_order) as sort_order FROM app.subjects WHERE class_cat_id = :classCatId AND active is true");

	$sth = $db->prepare("INSERT INTO app.subjects(subject_name, class_cat_id, teacher_id, created_by, parent_subject_id, sort_order, use_for_grading)
	VALUES(:subjectName, :classCatId, :teacherId, :userId, :parentSubjectId, :sortOrder, :forGrading)");


	$db->beginTransaction();

	$sortOrder->execute( array(':classCatId' => $classCatId) );
	$sort = $sortOrder->fetch(PDO::FETCH_OBJ);
	$sortOrder = ($sort && $sort->sort_order !== NULL ? $sort->sort_order + 1 : 1);

			$sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId,
						 ':teacherId' => $teacherId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId,
						 ':sortOrder' => $sortOrder,
						 ':forGrading' => $forGrading) );

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

$app->put('/updateSubject', function () use($app) {
	// Update subject

	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =		( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$subjectName =		( isset($allPostVars['subject_name']) ? $allPostVars['subject_name']: null);
	$classCatId =		( isset($allPostVars['class_cat_id']) ? $allPostVars['class_cat_id']: null);
	$teacherId =		( isset($allPostVars['teacher_id']) ? $allPostVars['teacher_id']: null);
	$parentSubjectId =	( isset($allPostVars['parent_subject_id']) ? $allPostVars['parent_subject_id']: null);
	$userId =			( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$forGrading =			( isset($allPostVars['use_for_grading']) ? $allPostVars['use_for_grading']: 'f');

	try
	{
		$db = getDB();
		$sth = $db->prepare("UPDATE app.subjects
												SET subject_name = :subjectName,
													class_cat_id = :classCatId,
													teacher_id = :teacherId,
													parent_subject_id = :parentSubjectId,
													use_for_grading = :forGrading,
													modified_date = now(),
													modified_by = :userId
												WHERE subject_id = :subjectId");

		$sth->execute( array(':subjectName' => $subjectName, ':classCatId' => $classCatId, ':teacherId' => $teacherId,
					 ':subjectId' => $subjectId, ':userId' => $userId, ':parentSubjectId' => $parentSubjectId, ':forGrading' => $forGrading
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

$app->put('/setSubjectStatus', function () use($app) {
	// Update subject status

	$allPostVars = json_decode($app->request()->getBody(),true);
	$subjectId =( isset($allPostVars['subject_id']) ? $allPostVars['subject_id']: null);
	$status =	( isset($allPostVars['status']) ? $allPostVars['status']: null);
	$userId =	( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

	try
	{
			$db = getDB();
			$sth = $db->prepare("UPDATE app.subjects
						SET active = :status,
							modified_date = now(),
							modified_by = :userId
						WHERE subject_id = :subjectId
						");
			$sth->execute( array(':subjectId' => $subjectId,
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

$app->put('/setSubjectSortOrder', function () use($app) {
	// Update subject sort order

	$allPostVars = json_decode($app->request()->getBody(),true);
	$userId =		( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$sortData =		( isset($allPostVars['data']) ? $allPostVars['data']: null);

	try
	{
			$db = getDB();
			$sth = $db->prepare("UPDATE app.subjects
						SET sort_order = :sortOrder,
							modified_date = now(),
							modified_by = :userId
						WHERE subject_id = :subjectId
						");

	$db->beginTransaction();
	foreach( $sortData as $item )
	{
		$subjectId =	( isset($item['subject_id']) ? $item['subject_id']: null);
		$sortOrder =	( isset($item['sort_order']) ? $item['sort_order']: null);
		$sth->execute( array(':subjectId' => $subjectId,
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

$app->get('/checkSubject/:subject_id', function ($subjectId) {
	// Check is subject can be deleted

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();
			$sth = $db->prepare("SELECT count(class_subject_id) as num_classes
							FROM app.class_subjects
							WHERE subject_id = :subjectId");
			$sth->execute( array(':subjectId' => $subjectId ) );

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

$app->delete('/deleteSubject/:subject_id', function ($subjectId) {
	// delete subject

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();

	$d1 = $db->prepare("DELETE FROM app.class_subject_exams
							WHERE class_sub_exam_id in (select class_sub_exam_id
														from app.class_subjects
														inner join app.class_subject_exams
														on class_subjects.class_subject_id = class_subject_exams.class_subject_id
														where subject_id = :subjectId)
						");
	$d2 = $db->prepare("DELETE FROM app.class_subjects WHERE subject_id = :subjectId");

	$d3 = $db->prepare("DELETE FROM app.subjects WHERE subject_id = :subjectId");

	$db->beginTransaction();
	$d1->execute( array(':subjectId' => $subjectId) );
	$d2->execute( array(':subjectId' => $subjectId) );
	$d3->execute( array(':subjectId' => $subjectId) );
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

$app->get('/getTeacherSubjects/:teacher_id(/:status)', function ($teacherId, $status = true) {
	//Show subjects for specific teacher

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();
	$sth = $db->prepare("SELECT subjects.subject_id, subject_name, subjects.teacher_id, subjects.active, subjects.class_cat_id, class_cat_name, use_for_grading,
				first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as teacher_name, class_subjects.class_id, class_name,
				(select count(*)
							from app.class_subjects
							INNER JOIN app.classes
								INNER JOIN app.students ON students.current_class = classes.class_id
							ON class_subjects.class_id = classes.class_id
							WHERE subject_id = subjects.subject_id) as num_students
				FROM app.subjects
				INNER JOIN app.class_cats
				ON subjects.class_cat_id = class_cats.class_cat_id AND class_cats.active is true
				INNER JOIN app.employees ON subjects.teacher_id = employees.emp_id
				INNER JOIN app.class_subjects
					INNER JOIN app.classes
					ON class_subjects.class_id = classes.class_id
				ON subjects.subject_id = class_subjects.subject_id
				WHERE subjects.teacher_id = :teacherId
				AND subjects.active = :status
				ORDER BY subjects.sort_order");

			$sth->execute( array(':teacherId' => $teacherId, ':status' => $status));

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

$app->get('/getTeacherClassSubjects/:teacher_id', function ($teacherId) {
	//Show class subjects for specific teacher
// Teachers can have classes, and be assigned subjects, get both results

	$app = \Slim\Slim::getInstance();

	try
	{
			$db = getDB();
	$sth = $db->prepare("SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
								classes.sort_order as class_order, subjects.sort_order as subject_order
									FROM app.class_subjects
									INNER JOIN app.classes
									ON class_subjects.class_id = classes.class_id
									INNER JOIN app.subjects
									ON class_subjects.subject_id = subjects.subject_id
									WHERE classes.teacher_id = :teacherId
									AND classes.active = true
									AND subjects.active = true
						UNION
						SELECT class_subject_id, class_name, subject_name, classes.class_id, subjects.subject_id, use_for_grading,
								classes.sort_order as class_order, subjects.sort_order as subject_order
									FROM app.subjects
									INNER JOIN app.class_subjects
										INNER JOIN app.classes
										ON class_subjects.class_id = classes.class_id
									ON subjects.subject_id = class_subjects.subject_id
									WHERE subjects.teacher_id = :teacherId
									AND subjects.active = true
									ORDER BY class_order, subject_order");

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
		$app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
		echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
	}

});


?>
