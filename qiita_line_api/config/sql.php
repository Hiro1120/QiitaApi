<?php

return [
  'insert_request_resend' => 'INSERT INTO request_resend_tbl (request_type, request, resend_time) VALUES (:value1, :value2, :value3)',
  'select_request_resend' => 'SELECT * FROM request_resend_tbl WHERE resend_time = 0',
  'update_request_resend' => 'UPDATE request_resend_tbl SET resend_time = 1 WHERE id = :value1',
  'delete_request_resend' => 'DELETE FROM request_resend_tbl WHERE id IN (:value1)',

  'select_property' => 'SELECT value FROM property_tbl WHERE `key` = "RESEND_LIMIT"',
  'select_condense' => 'SELECT * FROM request_resend_tbl WHERE resend_time = 1',
];