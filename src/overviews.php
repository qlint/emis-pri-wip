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
  <title>Data Overviews</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
  <h3 style="text-align:center;margin-top:15px;">This page overviews financial data in tables.</h3>
  <div class="limiter">
    <div class="container-table100">
  	   <div class="wrap-table100">
         <h4>Total amounts due and balances per fee item for the school. (Due this term)</h4><hr>
<?php
// $db = pg_connect("host=localhost port=5432 dbname=eduweb_dev user=postgres password=postgres");
$getDbname = 'eduweb_'.array_shift((explode('.', $_SERVER['HTTP_HOST'])));
$db = pg_connect("host=localhost port=5432 dbname=".$getDbname." user=postgres password=postgres");
/*$table1 = pg_query($db,"SELECT fee_item, payment_method, total_due, total_paid, balance
                      FROM(
                      	SELECT fee_item, q.payment_method,
                      	       sum(invoice_total) AS total_due,
                      	       sum(total_paid) AS total_paid,
                      	       sum(total_paid) - sum(invoice_total) AS balance
                      	FROM ( SELECT invoice_line_items.amount as invoice_total, fee_item, student_fee_items.payment_method, inv_item_id,
                      		       (
                      			       SELECT COALESCE(sum(payment_inv_items.amount), 0)
                      				       FROM app.payment_inv_items
                      				       INNER JOIN app.payments ON payment_inv_items.payment_id = payments.payment_id AND reversed is false
                      				       WHERE inv_item_id = invoice_line_items.inv_item_id
                      			) as total_paid
                      		FROM app.invoices
                      		INNER JOIN app.invoice_line_items
                      		INNER JOIN app.student_fee_items
                      		INNER JOIN app.fee_items ON student_fee_items.fee_item_id = fee_items.fee_item_id
                      					ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id AND student_fee_items.active = true
                      					ON invoices.inv_id = invoice_line_items.inv_id
                      		WHERE invoices.canceled = false
                      	      ) q
                      	      GROUP BY fee_item, q.payment_method
                      )v
                      WHERE balance <>0");*/
$table1 = pg_query($db,"SELECT fee_item, SUM(amount) AS total_due, SUM(total_paid) AS total_paid, SUM(balance) AS balance
FROM (
	SELECT fee_item, amount, total_paid, balance
	FROM (
		SELECT *FROM (
				SELECT *, amount - total_paid as balance
				FROM (
					SELECT  invoices.inv_id, inv_date, invoice_line_items.amount,
								coalesce((select sum(payment_inv_items.amount)
										from app.payment_inv_items
										inner join app.payments on payment_inv_items.payment_id = payments.payment_id
										where payment_inv_items.inv_item_id = invoice_line_items.inv_item_id
										AND reversed = false),0) as total_paid,
								date_part('year', due_date) AS due_date,
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
							WHERE students.active is true
							ORDER BY fee_item
							) q)x WHERE due_date = date_part('year', CURRENT_DATE))y)z
							--WHERE balance <> 0
							GROUP BY z.fee_item");

echo "<div class='table100 ver1 m-b-110'>";
echo "<table id='table1'>";
  echo "<div id='t1' class='table100-head'>";
    // echo "<table id='table1'>";
      echo "<thead>";
       echo "<tr class='row100 head'>";
         echo "<th class='cell100 column1'>FEE ITEM</th>";
         echo "<th class='cell100 column2'>PAYMENT METHOD</th>";
         echo "<th class='cell100 column3'>TOTAL DUE</th>";
         echo "<th class='cell100 column4'>TOTAL PAID</th>";
         echo "<th class='cell100 column5'>BALANCE</th>";
       echo "</tr>";
      echo "</thead>";
    // echo "</table>";
  echo "</div>";
  echo "<div class='table100-body js-pscroll'>";
    // echo "<table id='table1-2'>";
      echo "<tbody>";
        while ($row = pg_fetch_assoc($table1)) {
          echo "<tr class='row100 body'>";
             echo "<td class='cell100 column1'>" . $row['fee_item'] . "</td>";
             echo "<td class='cell100 column2'>" . $row['payment_method'] . "</td>";
             echo "<td class='cell100 column3'>" . number_format($row['total_due']) . "</td>";
             echo "<td class='cell100 column4'>" . number_format($row['total_paid']) . "</td>";
             echo "<td class='cell100 column5'>" . number_format($row['balance']) . "</td>";
         echo "</tr>";
        }
      echo "</tbody>";
    // echo "</table>";
  echo "</div>";
  echo "</table>";
 echo "</div>";

echo "<h4>Amount paid by each student for their fee items (Ordered by Names Ascending & Dates Descending)</h4><hr>";
 $table2 = pg_query($db,/*"SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name, fi.fee_item, pii.amount AS amount_paid, ili.amount AS default_amount, pii.creation_date AS date
                        FROM app.students s
                        INNER JOIN app.classes c ON s.current_class = c.class_cat_id
                        INNER JOIN app.student_fee_items sfi ON s.student_id = sfi.student_id
                        INNER JOIN app.fee_items fi ON sfi.fee_item_id = fi.fee_item_id
                        INNER JOIN app.invoice_line_items ili ON sfi.student_fee_item_id = ili.student_fee_item_id
                        INNER JOIN app.payment_inv_items pii ON ili.inv_item_id = pii.inv_item_id
                        INNER JOIN app.payments p ON pii.payment_id = p.payment_id
                        WHERE s.active IS TRUE AND c.active IS TRUE
                      ORDER BY student_name ASC, date DESC"*/
                    "SELECT * FROM (
                                  	SELECT s.first_name || ' ' || coalesce(s.middle_name,'') || ' ' || s.last_name AS student_name, c.class_name, fi.fee_item, pii.amount AS amount_paid, ili.amount AS default_amount, pii.creation_date AS date
                                          FROM app.students s
                                          INNER JOIN app.classes c ON s.current_class = c.class_cat_id
                                          INNER JOIN app.student_fee_items sfi ON s.student_id = sfi.student_id
                                          INNER JOIN app.fee_items fi ON sfi.fee_item_id = fi.fee_item_id
                                          INNER JOIN app.invoice_line_items ili ON sfi.student_fee_item_id = ili.student_fee_item_id
                                          INNER JOIN app.payment_inv_items pii ON ili.inv_item_id = pii.inv_item_id
                                          INNER JOIN app.payments p ON pii.payment_id = p.payment_id
                                          WHERE s.active IS TRUE AND c.active IS TRUE
                                          ORDER BY student_name ASC, date DESC
                                  )a WHERE date >= (SELECT start_date FROM app.terms WHERE now() between start_date and end_date)");

 // $col1 = NULL;
 echo "<div class='table100 ver1 m-b-110'>";
    echo "<table id='table2'>";
      echo "<div id='t2' class='table100-head'>";
         echo "<thead>";
          echo "<tr class='row100 head'>";
            echo "<th class='cell100 column1'>STUDENT NAME</th>";
            echo "<th class='cell100 column7'>CLASS</th>";
            echo "<th class='cell100 column8'>FEE ITEM</th>";
            echo "<th class='cell100 column9'>PAID</th>";
            echo "<th class='cell100 column10'>DEFAULT AMT</th>";
            echo "<th class='cell100 column11'>DATE</th>";
          echo "</tr>";
         echo "</thead>";
     echo "</div>";
     echo "<div class='table100-body js-pscroll'>";
       echo "<tbody>";
         while ($row2 = pg_fetch_assoc($table2)) {
           // $text1 = '';
           echo "<tr class='row100 body'>";
              echo "<td class='cell100 column1'>" . $row2['student_name'] . "</td>";
              echo "<td class='cell100 column7'>" . $row2['class_name'] . "</td>";
              echo "<td class='cell100 column8'>" . $row2['fee_item'] . "</td>";
              echo "<td class='cell100 column9'>" . number_format($row2['amount_paid']) . "</td>";
              echo "<td class='cell100 column10'>" . number_format($row2['default_amount']) . "</td>";
              echo "<td class='cell100 column11'>" . date( 'M j, Y', strtotime($row2['date'])) . "</td>";
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
                title: 'Fee-Item-Amounts-Due'
            },
            {
              extend: 'csvHtml5',
              title: 'Fee-Item-Amounts-Due'
          },
            {
                extend: 'pdfHtml5',
                title: 'Fee-Item-Amounts-Due'
            }
          ]
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
                title: 'All-Students-Paid-Fee-Items'
            },
            {
              extend: 'csvHtml5',
              title: 'All-Students-Paid-Fee-Items'
          },
            {
                extend: 'pdfHtml5',
                title: 'All-Students-Paid-Fee-Items'
            }
          ]
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
