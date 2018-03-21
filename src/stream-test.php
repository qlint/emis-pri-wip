<!--
NOTICE: To only view the data on this file on a browser, disable your internet connection,
else SMS messeges will be sent out

Also set the SMS API url and subscriber name in the <script> tag at the bottom of the page
on this -> "subscriber_name": "xxxxx"
and this -> var url = "http://xxxxx";
-->
<?php
header('Access-Control-Allow-Origin: *');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Stream Test</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<style>
  body,h3,table{margin-left: 15px;}
</style>
<body>
<h3>Form 4.</h3><hr>
<?php
$db = pg_connect("host=localhost port=5432 dbname=eduweb_dev user=postgres password=postgres");
$result = pg_query($db,"SELECT student_name, class_name, exam_type, subject_name, total_mark as marks, total_grade_weight as out_of, percentage, grade, sort_order
							FROM(
								SELECT  student_name, class_name, exam_type, subject_name, total_mark, total_grade_weight, ceil(total_mark::float/total_grade_weight::float*100) as percentage,
									(select grade from app.grading where (total_mark::float/total_grade_weight::float)*100 between min_mark and max_mark) as grade, sort_order
								FROM (
									SELECT class_name, class_subjects.class_id,class_subjects.subject_id,subject_name,exam_marks.student_id,students.first_name || ' ' || coalesce(students.middle_name,'') || ' ' || students.last_name AS student_name,coalesce(sum(case when subjects.parent_subject_id is null then mark end),0) as total_mark,
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
									AND term_id = 1
									AND subjects.parent_subject_id is null
									AND subjects.use_for_grading is true
									AND mark IS NOT NULL
									GROUP BY class_subjects.class_id, subjects.subject_name, exam_marks.student_id, class_subjects.subject_id, subjects.sort_order, use_for_grading, students.first_name, students.middle_name, students.last_name, class_name, exam_types.exam_type
								) q ORDER BY sort_order
							)v GROUP BY v.student_name, v.class_name, v.exam_type, v.subject_name, v.total_mark, v.total_grade_weight, v.percentage, v.grade, v.sort_order
							ORDER BY student_name ASC, sort_order ASC");


            $col1 = NULL;
            echo "<table id='table'>";
            while ($row = pg_fetch_assoc($result)) {
                $text1 = '';
                if ($row['student_name'] != $col1) {
                    $col1 = $row['student_name'];
                    $text1 = $col1;
                }
                echo "<tr>";
                echo "<td align='left' width='200'>" . $text1 . "</td>";
                echo "<td align='left' width='200'>" . $row['class_name'] . "</td>";
                echo "<td align='left' width='200'>" . $row['exam_type'] . "</td>";
                echo "<td align='left' width='200'>" . $row['subject_name'] . "</td>";
                echo "<td align='left' width='200'>" . $row['marks'] . "</td>";
                echo "<td align='left' width='200'>" . $row['out_of'] . "</td>";
                echo "<td align='left' width='200'>" . $row['percentage'] . "</td>";
                echo "<td align='left' width='200'>" . $row['grade'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
?>


</body>
</html>
