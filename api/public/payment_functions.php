<?php
$app->get('/getPaymentsReceived/:startDate/:endDate/:paymentStatus(/:studentStatus)', function ($startDate,$endDate, $paymentStatus = false, $studentStatus = null) {
  // Get all payment received for given date range

  $app = \Slim\Slim::getInstance();

  try
  {
      $db = getDB();
    $query = "SELECT payments.student_id,
             payment_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
             amount, payment_date, payments.payment_method,
             CASE WHEN replacement_payment = true THEN
             (SELECT array_agg(fee_item || ' Replacement')
                 FROM app.payment_replacement_items
                 INNER JOIN app.student_fee_items using (student_fee_item_id)
                 INNER JOIN app.fee_items using (fee_item_id)
                 WHERE payment_id = payments.payment_id
                 )
             ELSE
              (SELECT array_agg(fee_item || ' (Inv #' || invoices.inv_id || ')' )
                 FROM app.payment_inv_items
                 INNER JOIN app.invoices on payment_inv_items.inv_id  = invoices.inv_id and canceled = false
                 INNER JOIN app.invoice_line_items using (inv_item_id)
                 INNER JOIN app.student_fee_items using (student_fee_item_id)
                 INNER JOIN app.fee_items using (fee_item_id)
                 WHERE payment_id = payments.payment_id
                 )
             END as applied_to,
             class_name, class_id, class_cat_id, students.active as status, replacement_payment, reversed,
             (
              amount - coalesce((select coalesce(sum(amount),0) + coalesce((select sum(amount) from app.credits where payment_id = payments.payment_id ),0) as sum
                        from app.payment_inv_items
                        inner join app.invoices using (inv_id)
                         where payment_id = payments.payment_id
                         and canceled = false ),0)
            ) AS unapplied_amount
          FROM app.payments
          INNER JOIN app.students ON payments.student_id = students.student_id
          INNER JOIN app.classes ON students.current_class = classes.class_id
          WHERE payment_date between :startDate and :endDate
          AND payments.payment_method != 'Credit'
          AND reversed = :reversed
              ";
    $queryParams = array(':startDate' => $startDate, ':endDate' => $endDate, ':reversed' => $paymentStatus);

    if( $studentStatus != null )
    {
      // interested to pull payments for a specific student status
      $query .= "AND students.active = :status ";
      $queryParams[':status'] = $studentStatus;
    }


    $sth = $db->prepare($query);
    $sth->execute( $queryParams );
    $results = $sth->fetchAll(PDO::FETCH_OBJ);

    if($results) {
      foreach( $results as $result)
      {
        $result->applied_to = pg_array_parse($result->applied_to);
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

$app->get('/getPaymentsDue(/:startDate/:endDate)', function ($startDate=null,$endDate=null) {
    // Get all payment due for given date range
  // TO DO: not using start and end dates, only pulling the current month
  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
       $sth = $db->prepare("SELECT student_id,
                  first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                  total_due as amount, total_paid, balance,  due_date
              FROM app.invoice_balances2
              INNER JOIN app.students using (student_id)
              WHERE date_trunc('month',due_date) = date_trunc('month', now())
              AND balance < 0
              AND canceled is false");
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

$app->get('/getPaymentsPastDue(/:student_id)', function ($studentId=null) {
    // Get all payment due for given date range
  // TO DO: do I need to keep the student ID or can this be removed?
  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
       $sth = $db->prepare("SELECT invoice_balances2.student_id,
                  first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                  balance,  due_date
              FROM app.invoice_balances2
              INNER JOIN app.students on invoice_balances2.student_id = students.student_id
              WHERE due_date < now() /* - interval '1 mon' */
              AND balance < 0
              AND canceled is false ");
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

$app->get('/getTotalsForTerm', function () {
    // Get all invoice totals for current term

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
    // replacement payments do not have invoices, need to select these in addition to invoice payments to add to total paid amount
       $sth = $db->prepare("SELECT total_due, total_paid, total_paid - total_due as total_balance
              FROM (
                SELECT
                  coalesce(sum(total_amount),0) as total_due,
                  coalesce((select sum(amount)
                      from app.payments where payment_method != 'Credit'
                      and payment_date between (select start_date from app.current_term) and
                    coalesce((select start_date - interval '1 day' from app.next_term), (select end_date from app.current_term)) ),0) as total_paid
                FROM app.invoices
                WHERE due_date between (select start_date from app.current_term) and
                    coalesce((select start_date - interval '1 day' from app.next_term), (select end_date from app.current_term))
                AND canceled = false
              )q");
    $sth->execute();
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

$app->get('/getStudentBalances/:year(/:status)', function ($year, $status = true) {
    // Get all students balances

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
       $sth = $db->prepare("SELECT
                students.student_id,
                first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
                class_name, class_id, class_cat_id,
                sum(total_due) as total_due, sum(total_paid) as total_paid, sum(balance) as balance,
                (SELECT to_char(amount, '999,999,999.99') || ' - ' || to_char(payment_date,'Mon DD, YYYY')  FROM app.payments WHERE student_id = students.student_id ORDER BY payment_date desc LIMIT 1 ) as last_payment,
                (SELECT to_char(total_due, '999,999,999.99') || ' - ' || to_char(due_date,'Mon DD, YYYY')   FROM app.invoice_balances2 WHERE student_id = students.student_id AND due_date > now() ORDER BY due_date asc LIMIT 1 ) as next_payment
              FROM app.invoice_balances2
              INNER JOIN app.students
                INNER JOIN app.classes
                ON students.current_class = classes.class_id
              ON invoice_balances2.student_id = students.student_id
              WHERE/* invoice_balances2.due_date < now()
              AND */date_part('year', due_date) = :year
              AND students.active = :status
              AND canceled = false
              GROUP BY students.student_id, class_name, class_id, class_cat_id, first_name, middle_name, last_name");
    $sth->execute( array(':year' => $year, ':status' => $status) );
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

$app->get('/getInstallmentOptions', function () {
    // Get countries

  $app = \Slim\Slim::getInstance();

    try
    {
        $db = getDB();
        $sth = $db->prepare("SELECT installment_id, payment_plan_name FROM app.installment_options ORDER BY payment_plan_name");
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

$app->post('/addPayment', function () use($app) {
  // create payment
  $allPostVars = json_decode($app->request()->getBody(),true);

  $userId =         ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  $studentId =      ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $paymentDate =      ( isset($allPostVars['payment_date']) ? $allPostVars['payment_date']: null);
  $amount =         ( isset($allPostVars['amount']) ? $allPostVars['amount']: null);
  $paymentMethod =    ( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
  $slipChequeNo =     ( isset($allPostVars['slip_cheque_no']) ? $allPostVars['slip_cheque_no']: null);
  $replacementPayment =   ( isset($allPostVars['replacement_payment']) ? $allPostVars['replacement_payment']: null);
  $lineItems =      ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
  $replacementItems =   ( isset($allPostVars['replacement_items']) ? $allPostVars['replacement_items']: null);
  $hasCredit =      ( isset($allPostVars['hasCredit']) ? $allPostVars['hasCredit']: false);
  $creditAmt =      ( isset($allPostVars['creditAmt']) ? $allPostVars['creditAmt']: null);
  $updateCredit =     ( isset($allPostVars['updateCredit']) ? $allPostVars['updateCredit']: false);
  $amtApplied =     ( isset($allPostVars['amtApplied']) ? $allPostVars['amtApplied']: null);

  try
  {
    $db = getDB();
    $payment = $db->prepare("INSERT INTO app.payments(student_id, payment_date, amount, payment_method, slip_cheque_no, replacement_payment, created_by)
                  VALUES(:studentId, :paymentDate, :amount, :paymentMethod, :slipChequeNo, :replacementPayment, :userId)");

    $credit = $db->prepare("INSERT INTO app.credits(student_id, payment_id, amount, created_by)
                  VALUES(:studentId, currval('app.payments_payment_id_seq'), :creditAmt, :userId)");

    $getCredits = $db->prepare("SELECT credit_id, amount, amount  as credit_available
                  FROM app.credits
                  WHERE student_id = :studentId
                  ORDER BY creation_date");

    $updateCreditQry = $db->prepare("UPDATE app.credits
                    SET amount = :amtApplied,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE credit_id = :creditId");

    $deleteCredit = $db->prepare("DELETE FROM app.credits WHERE credit_id = :creditId");

    if( count($lineItems) > 0 )
    {
      $paymentItems = $db->prepare("INSERT INTO app.payment_inv_items(payment_id, inv_id, inv_item_id, amount, created_by)
                  VALUES(currval('app.payments_payment_id_seq'), :invId, :invItemId, :amount, :userId)");
    }
    if( count($replacementItems) > 0 )
    {
      $replaceItems = $db->prepare("INSERT INTO app.payment_replacement_items(payment_id, student_fee_item_id, amount, created_by)
                  VALUES(currval('app.payments_payment_id_seq'), :studenFeeItemId, :amount, :userId)");
    }

    $db->beginTransaction();


    $payment->execute( array(':studentId' => $studentId,
                 ':paymentDate' => $paymentDate,
                 ':amount' => $amount,
                 ':paymentMethod' => $paymentMethod,
                 ':slipChequeNo' => $slipChequeNo,
                 ':replacementPayment' => $replacementPayment,
                 ':userId' => $userId ) );

    if( count($lineItems) > 0 )
    {
      foreach( $lineItems as $lineItem )
      {
        $invItemId = ( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);
        $amount =   ( isset($lineItem['amount']) ? $lineItem['amount']: null);
        $invId =  ( isset($lineItem['inv_id']) ? $lineItem['inv_id']: null);

        $paymentItems->execute( array(':invId' => $invId,
                       ':invItemId' => $invItemId,
                       ':amount' => $amount,
                       ':userId' => $userId ) );
      }
    }

    if( count($replacementItems) > 0 )
    {
      foreach( $replacementItems as $replacementItem )
      {
        $studenFeeItemId = ( isset($replacementItem['student_fee_item_id']) ? $replacementItem['student_fee_item_id']: null);
        $amount = ( isset($replacementItem['amount']) ? $replacementItem['amount']: null);

        $replaceItems->execute( array(':studenFeeItemId' => $studenFeeItemId,
                       ':amount' => $amount,
                       ':userId' => $userId ) );
      }
    }

    if( $hasCredit )
    {
      $credit->execute(array(':studentId' => $studentId,
                  ':creditAmt' => $creditAmt,
                  ':userId' => $userId ) );
    }
    if( $updateCredit )
    {
      // since we displayed a sum of credit to the user, we don't know what credit to use
      // so we will need to pull all open credit, compare the amount available with the creditAmt
      // then update the credit records with the difference
      // if 0, delete record

      $getCredits->execute( array(':studentId' => $studentId) );
      $creditRows = $getCredits->fetchAll(PDO::FETCH_OBJ);

      $creditRemaining = $amtApplied;
      $curCredit = $creditRemaining;
      foreach($creditRows as $row)
      {
        // what is amount of credit applied
        $creditRemaining = $creditRemaining - $row->credit_available;
        if( $creditRemaining > 0 )
        {
          // the credit we are applying is greater than this credit entry
          // credit is all used, delete
          $deleteCredit->execute(array(':creditId' => $row->credit_id));
          /*
          $updateCreditQry->execute(array(':creditId' => $row->credit_id,
                  ':amtApplied' => $row->amount,
                  ':userId' => $userId ) );
          */
        }
        else
        {
          // if the credit entry has more credit than we are applying
          // need to update the amount_applied to the amount of credit left
          $updateCreditQry->execute(array(':creditId' => $row->credit_id,
                  ':amount' => $curCredit,
                  ':userId' => $userId ) );
        }
        $curCredit = $creditRemaining;
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

$app->get('/getPaymentDetails/:payment_id', function ($paymentId) {
  // Get all payment details

  $app = \Slim\Slim::getInstance();

  try
  {
     $db = getDB();

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

$app->put('/updatePayment', function() use($app){
   // Update payment
  $allPostVars = json_decode($app->request()->getBody(),true);
  $paymentId =      ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
  $userId =         ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  $studentId =      ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $paymentDate =      ( isset($allPostVars['payment_date']) ? $allPostVars['payment_date']: null);
  $amount =         ( isset($allPostVars['amount']) ? $allPostVars['amount']: null);
  $paymentMethod =    ( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
  $slipChequeNo =     ( isset($allPostVars['slip_cheque_no']) ? $allPostVars['slip_cheque_no']: null);
  $replacementPayment =   ( isset($allPostVars['replacement_payment']) ? $allPostVars['replacement_payment']: null);
  //$invId =        ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
  $lineItems =      ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
  $replacementItems =   ( isset($allPostVars['replacement_items']) ? $allPostVars['replacement_items']: null);
  $hasCredit =      ( isset($allPostVars['hasCredit']) ? $allPostVars['hasCredit']: false);
  $creditAmt =      ( isset($allPostVars['creditAmt']) ? $allPostVars['creditAmt']: null);
  $creditId =       ( isset($allPostVars['creditId']) ? $allPostVars['creditId']: null);

  try
  {
    $db = getDB();

    $updatePayment = $db->prepare("UPDATE app.payments
                    SET payment_date = :paymentDate,
                      amount = :amount,
                      payment_method = :paymentMethod,
                      slip_cheque_no = :slipChequeNo,
                      replacement_payment = :replacementPayment,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE payment_id = :paymentId");

    $credit = $db->prepare("INSERT INTO app.credits(student_id, payment_id, amount, created_by)
                  VALUES(:studentId, :paymentId, :creditAmt, :userId)");

    $updateCreditQry = $db->prepare("UPDATE app.credits
                    SET amount = :creditAmt,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE credit_id = :creditId");

    $deleteCredit = $db->prepare("DELETE FROM app.credits WHERE credit_id = :creditId");

    // prepare the possible statements
    if( count($lineItems) > 0 )
    {
      $itemUpdate = $db->prepare("UPDATE app.payment_inv_items
                    SET amount = :amount,
                      modified_date = now(),
                      modified_by = :userID
                    WHERE payment_inv_item_id = :paymentInvItemId");

      $itemInsert = $db->prepare("INSERT INTO app.payment_inv_items(payment_id, inv_id, inv_item_id, amount, created_by)
                    VALUES(:paymentId, :invId, :invItemId, :amount, :userId)");


      $deleteLine = $db->prepare("DELETE FROM app.payment_inv_items WHERE payment_inv_item_id = :paymentInvItemId");
    }
    else
    {
      $deleteAllLines = $db->prepare("DELETE FROM app.payment_inv_items WHERE payment_id = :paymentId");
    }

    if( count($replacementItems) > 0 )
    {
      $replaceItemUpdate = $db->prepare("UPDATE app.payment_replacement_items
                    SET amount = :amount,
                      modified_date = now(),
                      modified_by = :userID
                    WHERE payment_replace_item_id = :paymentReplaceItemId");

      $replaceItemInsert = $db->prepare("INSERT INTO app.payment_replacement_items(payment_id, student_fee_item_id, amount, created_by)
                    VALUES(:paymentId, :studentFeeItemId, :amount, :userId)");


      $replaceDeleteLine = $db->prepare("DELETE FROM app.payment_replacement_items WHERE payment_replace_item_id = :paymentReplaceItemId");
    }
    else
    {
      $deleteReplaceLines = $db->prepare("DELETE FROM app.payment_replacement_items WHERE payment_id = :paymentId");
    }

    // get what is already set for this payment
    $query = $db->prepare("SELECT payment_replace_item_id FROM app.payment_replacement_items WHERE payment_id = :paymentId");
    $query->execute( array('paymentId' => $paymentId) );
    $currentReplaceItems = $query->fetchAll(PDO::FETCH_OBJ);

    // get what is already set for this payment
    $query = $db->prepare("SELECT payment_inv_item_id FROM app.payment_inv_items WHERE payment_id = :paymentId");
    $query->execute( array('paymentId' => $paymentId) );
    $currentLineItems = $query->fetchAll(PDO::FETCH_OBJ);

    $db->beginTransaction();

    $updatePayment->execute( array(':paymentId' => $paymentId,
            ':paymentDate' => $paymentDate,
            ':amount' => $amount,
            ':paymentMethod' => $paymentMethod,
            ':slipChequeNo' => $slipChequeNo,
            ':replacementPayment' => $replacementPayment,
            ':userId' => $userId
    ) );

    if( count($lineItems) > 0 )
    {

      // loop through and add or update
      foreach( $lineItems as $lineItem )
      {
        $amount =       ( isset($lineItem['amount']) ? $lineItem['amount']: null);
        $paymentInvItemId = ( isset($lineItem['payment_inv_item_id']) ? $lineItem['payment_inv_item_id']: null);
        $invId =      ( isset($lineItem['inv_id']) ? $lineItem['inv_id']: null);
        $invItemId =    ( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);

        // this item exists, update it, else insert
        if( $paymentInvItemId !== null && !empty($paymentInvItemId) )
        {
          // need to update all terms, current and future of this year
          // leaving previous terms as they were
          $itemUpdate->execute(array(':amount' => $amount,
                        ':paymentInvItemId' => $paymentInvItemId,
                        ':userID' => $userId ));
        }
        else
        {
          // check if was previously added, if so, reactivate
          // else fee items is new, add it
          // needs to be added once term term if per term fee item

          $itemInsert->execute( array(
            ':paymentId' => $paymentId,
            ':invId' => $invId,
            ':invItemId' => $invItemId,
            ':amount' => $amount,
            ':userId' => $userId)
          );

        }
      }


      // look for items to remove
      // compare to what was passed in
      foreach( $currentLineItems as $currentLineItem )
      {
        $deleteMe = true;
        // if found, do not delete
        foreach( $lineItems as $lineItem )
        {
          if( isset($lineItem['payment_inv_item_id']) && $lineItem['payment_inv_item_id'] == $currentLineItem->payment_inv_item_id )
          {
            $deleteMe = false;
          }
        }

        if( $deleteMe )
        {
          $deleteLine->execute(array(':paymentInvItemId' => $currentLineItem->payment_inv_item_id));
        }
      }

    }
    else
    {
      $deleteAllLines->execute(array('paymentId' => $paymentId));
    }

    if( count($replacementItems) > 0 )
    {

      // loop through and add or update
      foreach( $replacementItems as $replacementItem )
      {
        $amount =         ( isset($replacementItem['amount']) ? $replacementItem['amount']: null);
        $paymentReplaceItemId = ( isset($replacementItem['payment_replace_item_id']) ? $replacementItem['payment_replace_item_id']: null);
        $studentFeeItemId =   ( isset($replacementItem['student_fee_item_id']) ? $replacementItem['student_fee_item_id']: null);

        // this item exists, update it, else insert
        if( $paymentReplaceItemId !== null && !empty($paymentReplaceItemId) )
        {
          // need to update all terms, current and future of this year
          // leaving previous terms as they were
          $replaceItemUpdate->execute(array(':amount' => $amount,
                        ':paymentReplaceItemId' => $paymentReplaceItemId,
                        ':userID' => $userId ));
        }
        else
        {
          // check if was previously added, if so, reactivate
          // else fee items is new, add it
          // needs to be added once term term if per term fee item

          $replaceItemInsert->execute( array(
            ':paymentId' => $paymentId,
            ':studentFeeItemId' => $studentFeeItemId,
            ':amount' => $amount,
            ':userId' => $userId)
          );

        }
      }


      // look for items to remove
      // compare to what was passed in
      foreach( $currentReplaceItems as $currentReplaceItem )
      {
        $deleteMe = true;

        // if found, do not delete
        foreach( $replacementItems as $replacementItem )
        {
          if( isset($replacementItem['payment_replace_item_id']) && $replacementItem['payment_replace_item_id'] == $currentReplaceItem->payment_replace_item_id )
          {
            $deleteMe = false;
          }
        }

        if( $deleteMe )
        {
          $replaceDeleteLine->execute(array(':paymentReplaceItemId' => $currentReplaceItem->payment_replace_item_id));
        }
      }

    }
    else
    {
      $deleteReplaceLines->execute(array('paymentId' => $paymentId));
    }

    if( $hasCredit )
    {
      if( $creditId !== null )
      {
        if( $creditAmt > 0 )
        {
          $updateCreditQry->execute(array(':creditId' => $creditId,
                    ':creditAmt' => $creditAmt,
                    ':userId' => $userId ) );
        }
        else
        {
          $deleteCredit->execute(array(':creditId' => $creditId));
        }
      }
      else
      {
        $credit->execute(array(':studentId' => $studentId,
                  ':paymentId' => $paymentId,
                  ':creditAmt' => $creditAmt,
                  ':userId' => $userId ) );
      }
    }

    $db->commit();

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

$app->put('/reversePayment', function () use($app) {
  // update payment to reversed
  $allPostVars = json_decode($app->request()->getBody(),true);
  $paymentId = ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
  $userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  try
    {
        $db = getDB();

    $updatePayment = $db->prepare("UPDATE app.payments
                    SET reversed = true,
                      reversed_date = now(),
                      reversed_by = :userId,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE payment_id = :paymentId");
    $removeCredit = $db->prepare("DELETE FROM app.credits WHERE payment_id = :paymentId");

    $db->beginTransaction();
    $updatePayment->execute( array(':paymentId' => $paymentId,
            ':userId' => $userId
    ) );
    $removeCredit->execute( array(':paymentId' => $paymentId) );
    $db->commit();

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

$app->put('/reactivatePayment', function() use($app) {
  // update payment to not reversed
  $allPostVars = json_decode($app->request()->getBody(),true);
  $studentId = ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $paymentId = ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
  $hasCredit = ( isset($allPostVars['hasCredit']) ? $allPostVars['hasCredit']: false);
  $creditAmt = ( isset($allPostVars['creditAmt']) ? $allPostVars['creditAmt']: null);
  $userId =   ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);

  try
    {
        $db = getDB();

    $updatePayment = $db->prepare("UPDATE app.payments
                    SET reversed = false,
                      reversed_date = null,
                      reversed_by = null,
                      modified_date = now(),
                      modified_by= :userId
                    WHERE payment_id = :paymentId");
    $credit = $db->prepare("INSERT INTO app.credits(student_id, payment_id, amount, created_by)
                  VALUES(:studentId, :paymentId, :creditAmt, :userId)");

    $db->beginTransaction();
    $updatePayment->execute( array(':paymentId' => $paymentId,
            ':userId' => $userId
    ) );
    $credit->execute(array(':studentId' => $studentId,
                  ':paymentId' => $paymentId,
                  ':creditAmt' => $creditAmt,
                  ':userId' => $userId ) );
    $db->commit();

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

$app->delete('/deletePayment/:payment_id', function ($paymentId){
  // delete payment
  $app = \Slim\Slim::getInstance();
  try
  {

    $db = getDB();
    $removePaymentItems = $db->prepare("DELETE FROM app.payment_inv_items WHERE payment_id = :paymentId");
    $removePayment = $db->prepare("DELETE FROM app.payments WHERE payment_id = :paymentId");
    $removeCredit = $db->prepare("DELETE FROM app.credits WHERE payment_id = :paymentId");
    $removeReplacment = $db->prepare("DELETE FROM app.payment_replacement_items WHERE payment_id = :paymentId");

    $db->beginTransaction();
    $removePaymentItems->execute( array(':paymentId' => $paymentId) );
    $removeCredit->execute( array(':paymentId' => $paymentId) );
    $removePayment->execute( array(':paymentId' => $paymentId) );
    $removeReplacment->execute( array(':paymentId' => $paymentId) );
    $db->commit();

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

$app->put('/applyCredit', function() use($app){
   // apply credit
  $allPostVars = json_decode($app->request()->getBody(),true);
  $paymentId =      ( isset($allPostVars['payment_id']) ? $allPostVars['payment_id']: null);
  $userId =         ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
  $studentId =      ( isset($allPostVars['student_id']) ? $allPostVars['student_id']: null);
  $paymentDate =      ( isset($allPostVars['payment_date']) ? $allPostVars['payment_date']: null);
  $amount =         ( isset($allPostVars['amount']) ? $allPostVars['amount']: null);
  $paymentMethod =    ( isset($allPostVars['payment_method']) ? $allPostVars['payment_method']: null);
  $slipChequeNo =     ( isset($allPostVars['slip_cheque_no']) ? $allPostVars['slip_cheque_no']: null);
  $replacementPayment =   ( isset($allPostVars['replacement_payment']) ? $allPostVars['replacement_payment']: null);
  $lineItems =      ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
  $replacementItems =   ( isset($allPostVars['replacement_items']) ? $allPostVars['replacement_items']: null);
  $hasCredit =      ( isset($allPostVars['hasCredit']) ? $allPostVars['hasCredit']: false);
  $creditAmt =      ( isset($allPostVars['creditAmt']) ? $allPostVars['creditAmt']: null);
  $creditId =       ( isset($allPostVars['creditId']) ? $allPostVars['creditId']: null);

  try
  {
    $db = getDB();

    $credit = $db->prepare("INSERT INTO app.credits(student_id, payment_id, amount, created_by)
                  VALUES(:studentId, :paymentId, :creditAmt, :userId)");

    $updateCreditQry = $db->prepare("UPDATE app.credits
                    SET amount = :creditAmt,
                      modified_date = now(),
                      modified_by = :userId
                    WHERE credit_id = :creditId");

    $deleteCredit = $db->prepare("DELETE FROM app.credits WHERE credit_id = :creditId");

    $itemInsert = $db->prepare("INSERT INTO app.payment_inv_items(payment_id, inv_id, inv_item_id, amount, created_by)
                    VALUES(:paymentId, :invId, :invItemId, :amount, :userId)");

    $db->beginTransaction();

    // loop through and add or update
    foreach( $lineItems as $lineItem )
    {
      $amount =           ( isset($lineItem['amount']) ? $lineItem['amount']: null);
      $paymentInvItemId = ( isset($lineItem['payment_inv_item_id']) ? $lineItem['payment_inv_item_id']: null);
      $invId =            ( isset($lineItem['inv_id']) ? $lineItem['inv_id']: null);
      $invItemId =        ( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);

      $itemInsert->execute( array(
        ':paymentId' => $paymentId,
        ':invId' => $invId,
        ':invItemId' => $invItemId,
        ':amount' => $amount,
        ':userId' => $userId)
      );
    }


    if( $hasCredit )
    {
      if( $creditId !== null )
      {
        if( $creditAmt > 0 )
        {
          $updateCreditQry->execute(array(':creditId' => $creditId,
                    ':creditAmt' => $creditAmt,
                    ':userId' => $userId ) );
        }
        else
        {
          $deleteCredit->execute(array(':creditId' => $creditId));
        }
      }
      else
      {
        $credit->execute(array(':studentId' => $studentId,
                  ':paymentId' => $paymentId,
                  ':creditAmt' => $creditAmt,
                  ':userId' => $userId ) );
      }
    }

    $db->commit();

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

?>
