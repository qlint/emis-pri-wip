<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<link rel="icon" type="image/png" href="components/overviewFiles/images/icons/favicon.ico"/>
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/vendor/animate/animate.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/vendor/select2/select2.min.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/css/util.css">
	<link rel="stylesheet" type="text/css" href="components/overviewFiles/css/main.css">
  <link rel="stylesheet" type="text/css" href="components/overviewFiles/css/jquery.dataTables.min.css">
  <link rel="stylesheet" type="text/css" href="components/overviewFiles/css/buttons.dataTables.min.css">
  <title>Streams Analysis</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <h3 style="text-align:center;margin-top:15px;">This page overviews the student's current performance in the entire stream.</h3>
  <div class="limiter">
    <div class="container-table100">
  	   <div class="wrap-table100">
         <h4>STREAM: Form 4</h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_dev user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");


/* -------------------------FORM 4 QUERY ------------------------- */
$table1 = pg_query($db,"SELECT student_name, english, kiswahili, mathematics, cre, physics, biology, chemistry, history, geography, bs_studies, computer, french, tot, round(tot::float/8) as percentage, (select grade from app.grading where round(tot::float/8) between min_mark and max_mark) as grade, pos FROM (
  SELECT t1.*, t2.avg as tot, t2.position as pos
FROM
    (
	/* CREATE EXTENSION tablefunc; */

SELECT *
FROM   crosstab('
SELECT student_name, subject_name, sum(marks) as marks FROM (
SELECT student_id, student_name, class_name, exam_type, subject_name, total_mark as marks, total_grade_weight as out_of, percentage, grade, sort_order
FROM(
	SELECT  student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
	FROM (
		SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
		FROM app.exam_marks
		INNER JOIN app.students ON exam_marks.student_id = students.student_id
		INNER JOIN app.class_subject_exams
		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
		INNER JOIN app.class_subjects
		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
		INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
		WHERE class_cats.entity_id = 15
		AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
		AND subjects.parent_subject_id is null
		AND subjects.use_for_grading is true
		AND mark IS NOT NULL
		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
	) q ORDER BY sort_order
)v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order
ORDER BY student_name ASC, sort_order ASC
)a
GROUP BY student_name, subject_name
ORDER BY 1
','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 15 LIMIT 1) order by sort_order') AS ct (student_name text, english bigint, kiswahili bigint, mathematics bigint, cre bigint, physics bigint, biology bigint, chemistry bigint, history bigint, geography bigint, bs_studies bigint, computer bigint, french bigint)

    ) AS t1
    FULL OUTER JOIN
    (
	SELECT * FROM (
		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 12 AND students.active is true) AS position_out_of
		FROM (
			SELECT sum(total_mark) AS avg, student_id, student_name, class_name
			FROM (
				SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
					(SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
					sort_order, exam_type_id, student_id, student_name, class_name
				FROM (
					SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
						subjects.sort_order, class_subject_exams.exam_type_id
					FROM app.exam_marks
					INNER JOIN app.students ON exam_marks.student_id = students.student_id
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								 ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								 ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_cats.entity_id = 15
					AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
					AND subjects.parent_subject_id is null
					AND subjects.use_for_grading is true
					AND students.student_id = exam_marks.student_id
					AND mark IS NOT NULL
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
				) q ORDER BY sort_order
			) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
		) AS FOO2
	) AS foo3
    ) AS t2
    ON t1.student_name = t2.student_name
    order by position ASC
    )foo4 order by pos ASC");
/* -------------------------FORM 4 TABLE ------------------------- */
echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table1'>";
  echo "<div id='t1' class='table100-head'>";
    // echo "<table id='table1'>";
      echo "<thead>";
       echo "<tr class='row100 head'>";
         echo "<th class='cell100 column1'>STUDENT NAME</th>";
         echo "<th class='cell100 column2'>Eng.</th>";
         echo "<th class='cell100 column3'>Kis.</th>";
         echo "<th class='cell100 column4'>Mth.</th>";
         echo "<th class='cell100 column5'>CRE</th>";
         echo "<th class='cell100 column6'>Phy.</th>";
         echo "<th class='cell100 column7'>Bio.</th>";
         echo "<th class='cell100 column8'>Chm.</th>";
         echo "<th class='cell100 column9'>Hst.</th>";
         echo "<th class='cell100 column10'>Geo.</th>";
         echo "<th class='cell100 column11'>B/S.</th>";
         echo "<th class='cell100 column12'>Comp.</th>";
         echo "<th class='cell100 column13'>Fnch.</th>";
         echo "<th class='cell100 column14'>TOT.</th>";
         echo "<th class='cell100 column15'>%</th>";
         echo "<th class='cell100 column16'>GRD.</th>";
         echo "<th class='cell100 column17'>POS.</th>";
       echo "</tr>";
      echo "</thead>";
    // echo "</table>";
  echo "</div>";
  echo "<div class='table100-body js-pscroll'>";
    // echo "<table id='table1-2'>";
      echo "<tbody>";
        while ($row = pg_fetch_assoc($table1)) {
          echo "<tr class='row100 body'>";
             echo "<td class='cell100 column1'>" . $row['student_name'] . "</td>";
             echo "<td class='cell100 column2'>" . $row['english'] . "</td>";
             echo "<td class='cell100 column3'>" . $row['kiswahili'] . "</td>";
             echo "<td class='cell100 column4'>" . $row['mathematics'] . "</td>";
             echo "<td class='cell100 column5'>" . $row['cre'] . "</td>";
             echo "<td class='cell100 column6'>" . $row['physics'] . "</td>";
             echo "<td class='cell100 column7'>" . $row['biology'] . "</td>";
             echo "<td class='cell100 column8'>" . $row['chemistry'] . "</td>";
             echo "<td class='cell100 column9'>" . $row['history'] . "</td>";
             echo "<td class='cell100 column10'>" . $row['geography'] . "</td>";
             echo "<td class='cell100 column11'>" . $row['bs_studies'] . "</td>";
             echo "<td class='cell100 column12'>" . $row['computer'] . "</td>";
             echo "<td class='cell100 column13'>" . $row['french'] . "</td>";
             echo "<td class='cell100 column14'>" . $row['tot'] . "</td>";
             echo "<td class='cell100 column15'>" . $row['percentage'] . "</td>";
             echo "<td class='cell100 column16'>" . $row['grade'] . "</td>";
             echo "<td class='cell100 column17'>" . $row['pos'] . "</td>";
         echo "</tr>";
        }
      echo "</tbody>";
    // echo "</table>";
  echo "</div>";
  echo "</table>";
 echo "</div>";

echo "<h4>STREAM: Form 3</h4><hr>";
/* -------------------------FORM 3 QUERY ------------------------- */
 $table2 = pg_query($db,"SELECT student_name, english, kiswahili, mathematics, cre, physics, biology, chemistry, history, geography, bs_studies, computer, french, tot, round(tot::float/8) as percentage, (select grade from app.grading where round(tot::float/8) between min_mark and max_mark) as grade, pos FROM (
   SELECT t1.*, t2.avg as tot, t2.position as pos
FROM
    (
	/* CREATE EXTENSION tablefunc; */

SELECT *
FROM   crosstab('
SELECT student_name, subject_name, sum(marks) as marks FROM (
SELECT student_id, student_name, class_name, exam_type, subject_name, total_mark as marks, total_grade_weight as out_of, percentage, grade, sort_order
FROM(
	SELECT  student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
	FROM (
		SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
		FROM app.exam_marks
		INNER JOIN app.students ON exam_marks.student_id = students.student_id
		INNER JOIN app.class_subject_exams
		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
		INNER JOIN app.class_subjects
		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
		INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
		WHERE class_cats.entity_id = 14
		AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
		AND subjects.parent_subject_id is null
		AND subjects.use_for_grading is true
		AND mark IS NOT NULL
		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
	) q ORDER BY sort_order
)v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order
ORDER BY student_name ASC, sort_order ASC
)a
GROUP BY student_name, subject_name
ORDER BY 1
','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 14 LIMIT 1) order by sort_order') AS ct (student_name text, english bigint, kiswahili bigint, mathematics bigint, cre bigint, physics bigint, biology bigint, chemistry bigint, history bigint, geography bigint, bs_studies bigint, computer bigint, french bigint)

    ) AS t1
    FULL OUTER JOIN
    (
	SELECT * FROM (
		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 12 AND students.active is true) AS position_out_of
		FROM (
			SELECT sum(total_mark) AS avg, student_id, student_name, class_name
			FROM (
				SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
					(SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
					sort_order, exam_type_id, student_id, student_name, class_name
				FROM (
					SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
						subjects.sort_order, class_subject_exams.exam_type_id
					FROM app.exam_marks
					INNER JOIN app.students ON exam_marks.student_id = students.student_id
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								 ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								 ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_cats.entity_id = 14
					AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
					AND subjects.parent_subject_id is null
					AND subjects.use_for_grading is true
					AND students.student_id = exam_marks.student_id
					AND mark IS NOT NULL
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
				) q ORDER BY sort_order
			) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
		) AS FOO2
	) AS foo3
    ) AS t2
    ON t1.student_name = t2.student_name
    order by position ASC
    )foo4 order by pos ASC");

/* -------------------------FORM 3 TABLE ------------------------- */
 echo "<div class='table100 ver1 m-b-110'>";
    echo "<table id='table2'>";
      echo "<div id='t2' class='table100-head'>";
         echo "<thead>";
          echo "<tr class='row100 head'>";
            echo "<th class='cell100 column1'>STUDENT NAME</th>";
            echo "<th class='cell100 column2'>Eng.</th>";
            echo "<th class='cell100 column3'>Kis.</th>";
            echo "<th class='cell100 column4'>Mth.</th>";
            echo "<th class='cell100 column5'>CRE</th>";
            echo "<th class='cell100 column6'>Phy.</th>";
            echo "<th class='cell100 column7'>Bio.</th>";
            echo "<th class='cell100 column8'>Chm.</th>";
            echo "<th class='cell100 column9'>Hst.</th>";
            echo "<th class='cell100 column10'>Geo.</th>";
            echo "<th class='cell100 column11'>B/S.</th>";
            echo "<th class='cell100 column12'>Comp.</th>";
            echo "<th class='cell100 column13'>Fnch.</th>";
            echo "<th class='cell100 column14'>TOT.</th>";
            echo "<th class='cell100 column15'>%</th>";
            echo "<th class='cell100 column16'>GRD.</th>";
            echo "<th class='cell100 column17'>POS.</th>";
          echo "</tr>";
         echo "</thead>";
     echo "</div>";
     echo "<div class='table100-body js-pscroll'>";
       echo "<tbody>";
         while ($row2 = pg_fetch_assoc($table2)) {
           // $text1 = '';
           echo "<td class='cell100 column1'>" . $row2['student_name'] . "</td>";
           echo "<td class='cell100 column2'>" . $row2['english'] . "</td>";
           echo "<td class='cell100 column3'>" . $row2['kiswahili'] . "</td>";
           echo "<td class='cell100 column4'>" . $row2['mathematics'] . "</td>";
           echo "<td class='cell100 column5'>" . $row2['cre'] . "</td>";
           echo "<td class='cell100 column6'>" . $row2['physics'] . "</td>";
           echo "<td class='cell100 column7'>" . $row2['biology'] . "</td>";
           echo "<td class='cell100 column8'>" . $row2['chemistry'] . "</td>";
           echo "<td class='cell100 column9'>" . $row2['history'] . "</td>";
           echo "<td class='cell100 column10'>" . $row2['geography'] . "</td>";
           echo "<td class='cell100 column11'>" . $row2['bs_studies'] . "</td>";
           echo "<td class='cell100 column12'>" . $row2['computer'] . "</td>";
           echo "<td class='cell100 column13'>" . $row2['french'] . "</td>";
           echo "<td class='cell100 column14'>" . $row2['tot'] . "</td>";
           echo "<td class='cell100 column15'>" . $row2['percentage'] . "</td>";
           echo "<td class='cell100 column16'>" . $row2['grade'] . "</td>";
           echo "<td class='cell100 column17'>" . $row2['pos'] . "</td>";
          echo "</tr>";
         }
       echo "</tbody>";
     echo "</div>";
   echo "</table>";
   echo "</div>";


   echo "<h4>STREAM: Form 2</h4><hr>";
   /* -------------------------FORM 2 QUERY ------------------------- */
    $table3 = pg_query($db,"SELECT student_name, english, kiswahili, mathematics, cre, physics, biology, chemistry, history, geography, bs_studies, computer, french, tot, round(tot::float/8) as percentage, (select grade from app.grading where round(tot::float/8) between min_mark and max_mark) as grade, pos FROM (
      SELECT t1.*, t2.avg as tot, t2.position as pos
FROM
    (
	/* CREATE EXTENSION tablefunc; */

SELECT *
FROM   crosstab('
SELECT student_name, subject_name, sum(marks) as marks FROM (
SELECT student_id, student_name, class_name, exam_type, subject_name, total_mark as marks, total_grade_weight as out_of, percentage, grade, sort_order
FROM(
	SELECT  student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
	FROM (
		SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
		FROM app.exam_marks
		INNER JOIN app.students ON exam_marks.student_id = students.student_id
		INNER JOIN app.class_subject_exams
		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
		INNER JOIN app.class_subjects
		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
		INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
		WHERE class_cats.entity_id = 13
		AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
		AND subjects.parent_subject_id is null
		AND subjects.use_for_grading is true
		AND mark IS NOT NULL
		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
	) q ORDER BY sort_order
)v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order
ORDER BY student_name ASC, sort_order ASC
)a
GROUP BY student_name, subject_name
ORDER BY 1
','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 13 LIMIT 1) order by sort_order') AS ct (student_name text, english bigint, kiswahili bigint, mathematics bigint, cre bigint, physics bigint, biology bigint, chemistry bigint, history bigint, geography bigint, bs_studies bigint, computer bigint, french bigint)

    ) AS t1
    FULL OUTER JOIN
    (
	SELECT * FROM (
		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 12 AND students.active is true) AS position_out_of
		FROM (
			SELECT sum(total_mark) AS avg, student_id, student_name, class_name
			FROM (
				SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
					(SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
					sort_order, exam_type_id, student_id, student_name, class_name
				FROM (
					SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
						subjects.sort_order, class_subject_exams.exam_type_id
					FROM app.exam_marks
					INNER JOIN app.students ON exam_marks.student_id = students.student_id
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								 ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								 ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_cats.entity_id = 13
					AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
					AND subjects.parent_subject_id is null
					AND subjects.use_for_grading is true
					AND students.student_id = exam_marks.student_id
					AND mark IS NOT NULL
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
				) q ORDER BY sort_order
			) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
		) AS FOO2
	) AS foo3
    ) AS t2
    ON t1.student_name = t2.student_name
    order by position ASC
    )foo4 order by pos ASC");

/* -------------------------FORM 2 TABLE ------------------------- */
   echo "<div class='table100 ver1 m-b-110'>";
      echo "<table id='table3'>";
        echo "<div id='t3' class='table100-head'>";
           echo "<thead>";
            echo "<tr class='row100 head'>";
              echo "<th class='cell100 column1'>STUDENT NAME</th>";
              echo "<th class='cell100 column2'>Eng.</th>";
              echo "<th class='cell100 column3'>Kis.</th>";
              echo "<th class='cell100 column4'>Mth.</th>";
              echo "<th class='cell100 column5'>CRE</th>";
              echo "<th class='cell100 column6'>Phy.</th>";
              echo "<th class='cell100 column7'>Bio.</th>";
              echo "<th class='cell100 column8'>Chm.</th>";
              echo "<th class='cell100 column9'>Hst.</th>";
              echo "<th class='cell100 column10'>Geo.</th>";
              echo "<th class='cell100 column11'>B/S.</th>";
              echo "<th class='cell100 column12'>Comp.</th>";
              echo "<th class='cell100 column13'>Fnch.</th>";
              echo "<th class='cell100 column14'>TOT.</th>";
              echo "<th class='cell100 column15'>%</th>";
              echo "<th class='cell100 column16'>GRD.</th>";
              echo "<th class='cell100 column17'>POS.</th>";
            echo "</tr>";
           echo "</thead>";
       echo "</div>";
       echo "<div class='table100-body js-pscroll'>";
         echo "<tbody>";
           while ($row3 = pg_fetch_assoc($table3)) {
             // $text1 = '';
             echo "<td class='cell100 column1'>" . $row3['student_name'] . "</td>";
             echo "<td class='cell100 column2'>" . $row3['english'] . "</td>";
             echo "<td class='cell100 column3'>" . $row3['kiswahili'] . "</td>";
             echo "<td class='cell100 column4'>" . $row3['mathematics'] . "</td>";
             echo "<td class='cell100 column5'>" . $row3['cre'] . "</td>";
             echo "<td class='cell100 column6'>" . $row3['physics'] . "</td>";
             echo "<td class='cell100 column7'>" . $row3['biology'] . "</td>";
             echo "<td class='cell100 column8'>" . $row3['chemistry'] . "</td>";
             echo "<td class='cell100 column9'>" . $row3['history'] . "</td>";
             echo "<td class='cell100 column10'>" . $row3['geography'] . "</td>";
             echo "<td class='cell100 column11'>" . $row3['bs_studies'] . "</td>";
             echo "<td class='cell100 column12'>" . $row3['computer'] . "</td>";
             echo "<td class='cell100 column13'>" . $row3['french'] . "</td>";
             echo "<td class='cell100 column14'>" . $row3['tot'] . "</td>";
             echo "<td class='cell100 column15'>" . $row3['percentage'] . "</td>";
             echo "<td class='cell100 column16'>" . $row3['grade'] . "</td>";
             echo "<td class='cell100 column17'>" . $row3['pos'] . "</td>";
            echo "</tr>";
           }
         echo "</tbody>";
       echo "</div>";
     echo "</table>";
     echo "</div>";


     echo "<h4>STREAM: Form 1</h4><hr>";
     /* -------------------------FORM 1 QUERY ------------------------- */
      $table4 = pg_query($db,"SELECT student_name, english, kiswahili, mathematics, cre, physics, biology, chemistry, history, geography, bs_studies, computer, french, tot, round(tot::float/12) as percentage, (select grade from app.grading where round(tot::float/12) between min_mark and max_mark) as grade, pos FROM (
        SELECT t1.*, t2.avg as tot, t2.position as pos
FROM
    (
	/* CREATE EXTENSION tablefunc; */

SELECT *
FROM   crosstab('
SELECT student_name, subject_name, sum(marks) as marks FROM (
SELECT student_id, student_name, class_name, exam_type, subject_name, total_mark as marks, total_grade_weight as out_of, percentage, grade, sort_order
FROM(
	SELECT  student_id, student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
		(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
	FROM (
		SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || '' '' || coalesce(students.middle_name,'''') || '' '' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
			coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,subjects.sort_order, exam_type
		FROM app.exam_marks
		INNER JOIN app.students ON exam_marks.student_id = students.student_id
		INNER JOIN app.class_subject_exams
		INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
		INNER JOIN app.class_subjects
		INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
					ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
					ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
		INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
		INNER JOIN app.class_cats ON exam_types.class_cat_id = class_cats.class_cat_id
		WHERE class_cats.entity_id = 12
		AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
		AND subjects.parent_subject_id is null
		AND subjects.use_for_grading is true
		AND mark IS NOT NULL
		GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
	) q ORDER BY sort_order
)v GROUP BY v.student_id, v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order
ORDER BY student_name ASC, sort_order ASC
)a
GROUP BY student_name, subject_name
ORDER BY 1
','SELECT subject_name FROM app.subjects WHERE class_cat_id = (SELECT class_cat_id FROM app.class_cats WHERE entity_id = 12 LIMIT 1) order by sort_order') AS ct (student_name text, english bigint, kiswahili bigint, mathematics bigint, cre bigint, physics bigint, biology bigint, chemistry bigint, history bigint, geography bigint, bs_studies bigint, computer bigint, french bigint)

    ) AS t1
    FULL OUTER JOIN
    (
	SELECT * FROM (
		SELECT avg, student_id, student_name, class_name, rank() over(order by avg desc) AS position,
			(SELECT count(*) FROM app.students INNER JOIN app.classes ON students.current_class = classes.class_id INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id WHERE class_cats.entity_id = 12 AND students.active is true) AS position_out_of
		FROM (
			SELECT sum(total_mark) AS avg, student_id, student_name, class_name
			FROM (
				SELECT  subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
					(SELECT grade FROM app.grading WHERE (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) AS grade,
					sort_order, exam_type_id, student_id, student_name, class_name
				FROM (
					SELECT classes.class_id, class_subjects.subject_id, subject_name, exam_marks.student_id, students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name, classes.class_name,
						coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
						coalesce(sum(case when subjects.parent_subject_id is null then grade_weight end),0) as total_grade_weight,
						subjects.sort_order, class_subject_exams.exam_type_id
					FROM app.exam_marks
					INNER JOIN app.students ON exam_marks.student_id = students.student_id
					INNER JOIN app.class_subject_exams
					INNER JOIN app.exam_types ON class_subject_exams.exam_type_id = exam_types.exam_type_id
					INNER JOIN app.class_subjects
					INNER JOIN app.classes ON class_subjects.class_id = classes.class_id
					INNER JOIN app.class_cats ON classes.class_cat_id = class_cats.class_cat_id
					INNER JOIN app.subjects ON class_subjects.subject_id = subjects.subject_id AND subjects.active is true
								 ON class_subject_exams.class_subject_id = class_subjects.class_subject_id AND class_subjects.active is true
								 ON exam_marks.class_sub_exam_id = class_subject_exams.class_sub_exam_id
					WHERE class_cats.entity_id = 12
					AND term_id = (SELECT t.term_id FROM app.terms t WHERE now() >= t.start_date AND t.end_date > now())
					AND subjects.parent_subject_id is null
					AND subjects.use_for_grading is true
					AND students.student_id = exam_marks.student_id
					AND mark IS NOT NULL
					GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, class_subject_exams.exam_type_id,classes.class_id, students.first_name, students.middle_name, students.last_name
				) q ORDER BY sort_order
			) AS foo GROUP BY student_id,student_name, class_name ORDER BY avg DESC
		) AS FOO2
	) AS foo3
    ) AS t2
    ON t1.student_name = t2.student_name
    order by position ASC
    )foo4 order by pos ASC");

  /* -------------------------FORM 1 TABLE ------------------------- */
     echo "<div class='table100 ver1 m-b-110'>";
        echo "<table id='table4'>";
          echo "<div id='t4' class='table100-head'>";
             echo "<thead>";
              echo "<tr class='row100 head'>";
                echo "<th class='cell100 column1'>STUDENT NAME</th>";
                echo "<th class='cell100 column2'>Eng.</th>";
                echo "<th class='cell100 column3'>Kis.</th>";
                echo "<th class='cell100 column4'>Mth.</th>";
                echo "<th class='cell100 column5'>CRE</th>";
                echo "<th class='cell100 column6'>Phy.</th>";
                echo "<th class='cell100 column7'>Bio.</th>";
                echo "<th class='cell100 column8'>Chm.</th>";
                echo "<th class='cell100 column9'>Hst.</th>";
                echo "<th class='cell100 column10'>Geo.</th>";
                echo "<th class='cell100 column11'>B/S.</th>";
                echo "<th class='cell100 column12'>Comp.</th>";
                echo "<th class='cell100 column13'>Fnch.</th>";
                echo "<th class='cell100 column14'>TOT.</th>";
                echo "<th class='cell100 column15'>%</th>";
                echo "<th class='cell100 column16'>GRD.</th>";
                echo "<th class='cell100 column17'>POS.</th>";
              echo "</tr>";
             echo "</thead>";
         echo "</div>";
         echo "<div class='table100-body js-pscroll'>";
           echo "<tbody>";
             while ($row4 = pg_fetch_assoc($table4)) {
               // $text1 = '';
               echo "<td class='cell100 column1'>" . $row4['student_name'] . "</td>";
               echo "<td class='cell100 column2'>" . $row4['english'] . "</td>";
               echo "<td class='cell100 column3'>" . $row4['kiswahili'] . "</td>";
               echo "<td class='cell100 column4'>" . $row4['mathematics'] . "</td>";
               echo "<td class='cell100 column5'>" . $row4['cre'] . "</td>";
               echo "<td class='cell100 column6'>" . $row4['physics'] . "</td>";
               echo "<td class='cell100 column7'>" . $row4['biology'] . "</td>";
               echo "<td class='cell100 column8'>" . $row4['chemistry'] . "</td>";
               echo "<td class='cell100 column9'>" . $row4['history'] . "</td>";
               echo "<td class='cell100 column10'>" . $row4['geography'] . "</td>";
               echo "<td class='cell100 column11'>" . $row4['bs_studies'] . "</td>";
               echo "<td class='cell100 column12'>" . $row4['computer'] . "</td>";
               echo "<td class='cell100 column13'>" . $row4['french'] . "</td>";
               echo "<td class='cell100 column14'>" . $row4['tot'] . "</td>";
               echo "<td class='cell100 column15'>" . $row4['percentage'] . "</td>";
               echo "<td class='cell100 column16'>" . $row4['grade'] . "</td>";
               echo "<td class='cell100 column17'>" . $row4['pos'] . "</td>";
              echo "</tr>";
             }
           echo "</tbody>";
         echo "</div>";
       echo "</table>";
       echo "</div>";
?>
      </div>
    </div>
  </div>
	<script src="components/overviewFiles/vendor/jquery/jquery-3.2.1.min.js"></script>
	<script src="components/overviewFiles/vendor/bootstrap/js/popper.js"></script>
	<script src="components/overviewFiles/vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="components/overviewFiles/vendor/select2/select2.min.js"></script>
	<script src="components/overviewFiles/vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
	<script>
		$('.js-pscroll').each(function(){
			var ps = new PerfectScrollbar(this);

			$(window).on('resize', function(){
				ps.update();
			})
		});


	</script>
	<script src="components/overviewFiles/js/main.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#table1').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: 'Form-4'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-4'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-4'
            }
          ],
          "order": [[ 16, "asc" ]]
      } );
      $('#table2').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: 'Form-3'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-3'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-3'
            }
          ],
          "order": [[ 16, "asc" ]]
      } );
      $('#table3').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: 'Form-2'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-2'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-2'
            }
          ],
          "order": [[ 16, "asc" ]]
      } );
      $('#table4').DataTable( {
          fixedHeader: true,
          dom: 'Bfrtip',
          buttons: [
              // 'excelHtml5',
              // 'csvHtml5',
              // 'pdfHtml5',
              {
                extend: 'excelHtml5',
                title: 'Form-1'
            },
            {
              extend: 'csvHtml5',
              title: 'Form-1'
          },
            {
                extend: 'pdfHtml5',
                title: 'Form-1'
            }
          ],
          "order": [[ 16, "asc" ]]
      } );
    } );
  </script>
  <script src="components/overviewFiles/js/jquery.dataTables.min.js"></script>
  <script src="components/overviewFiles/js/dataTables.buttons.min.js"></script>
  <script src="components/overviewFiles/js/jszip.min.js"></script>
  <script src="components/overviewFiles/js/pdfmake.min.js"></script>
  <script src="components/overviewFiles/js/vfs_fonts.js"></script>
  <script src="components/overviewFiles/js/buttons.html5.min.js"></script>

</body>
</html>
