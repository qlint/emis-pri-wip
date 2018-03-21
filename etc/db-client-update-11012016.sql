CREATE OR REPLACE VIEW app.previous_term AS 
 SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number
   FROM app.terms
  WHERE terms.start_date < (( SELECT current_term.start_date
           FROM app.current_term))
  ORDER BY terms.start_date DESC
 LIMIT 1;