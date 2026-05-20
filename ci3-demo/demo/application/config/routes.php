<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller']   = 'hygiene/index';
$route['404_override']         = '';
$route['translate_uri_dashes'] = FALSE;

/* ===================== Requests ===================== */
$route['requests']                 = 'Requests/index';
$route['requests/create']          = 'Requests/create';
$route['requests/store']           = 'Requests/store';
$route['requests/view/(:num)']     = 'Requests/view/$1';
$route['requests/price/(:num)']    = 'Requests/price/$1';
$route['requests/request_index']   = 'Requests/index';
$route['requests/print/(:num)']    = 'Requests/print_doc/$1';
$route['requests/edit/(:num)']     = 'Requests/edit/$1';
$route['requests/update/(:num)']   = 'Requests/update/$1';
$route['requests/cancel/(:num)']   = 'Requests/cancel/$1';   // ← fixed ($route)
$route['requests/customer/(:num)'] = 'Requests/customer/$1';

/* ===================== Hygiene core ===================== */
$route['hygiene']                                    = 'hygiene/index';
$route['hygiene/select_form']                        = 'hygiene/select_form';
$route['hygiene/prepare']                            = 'hygiene/prepare';
$route['hygiene/store']                              = 'hygiene/store';
$route['hygiene/view_attendances']                   = 'hygiene/view_attendances';
$route['hygiene/view/(:num)/(:any)']                 = 'hygiene/view/$1/$2';
$route['hygiene/view_batch/(:num)']                  = 'hygiene/view_batch/$1';
$route['hygiene/edit_class_attendance/(:num)']       = 'hygiene/edit_by_id/$1';
$route['hygiene/edit_class_attendance/(:num)/(:any)']= 'hygiene/edit_class_attendance/$1/$2';
$route['hygiene/start_batch']                        = 'hygiene/start_batch';
$route['hygiene/prepare_direct/(:num)/(:any)']       = 'hygiene/prepare_direct/$1/$2';

/* ===================== Hygiene reports ===================== */
$route['hygiene/report-hygiene']         = 'hygiene/report_hygiene';
$route['hygiene/report-hygiene/results'] = 'hygiene/report_hygiene_results';

/* ===================== Student hygiene ===================== */
$route['hygiene/student/(:num)']         = 'hygiene/student_report/$1';
$route['hygiene/student/(:num)/results'] = 'hygiene/student_report_results/$1';
$route['hygiene/student-go']             = 'hygiene/student_go';

/* ===================== Attendance delete ===================== */
$route['hygiene/attendance/delete-batch']       = 'hygiene/delete_batch';
$route['hygiene/attendance/delete-row/(:num)']  = 'hygiene/delete_row/$1';
$route['hygiene/attendance/delete-row-by-keys'] = 'hygiene/delete_row_by_keys';

/* ===================== Follow-ups ===================== */
$route['hygiene/followups']                         = 'hygiene/followups';
$route['hygiene/followups/resolve']                 = 'hygiene/followups_resolve';
$route['hygiene/followups/bulk-resolve']            = 'hygiene/followups_bulk_resolve';
$route['hygiene/followups/resolve-by-student-type'] = 'hygiene/followups_resolve_by_student_type';

/* ===================== Higaad – Teacher ===================== */
$route['teacher/higaad']           = 'Higaad/index';
$route['teacher/higaad/prepare']   = 'Higaad/prepare';
$route['teacher/higaad/store_row'] = 'Higaad/store_row';
$route['teacher/higaad/available'] = 'Higaad/available_chapters';
$route['teacher/higaad/submit_class'] = 'Higaad/submit_class';
$route['teacher/higaad/fill_class']   = 'Higaad/fill_class';

/* ===================== Higaad – Admin ===================== */
$route['admin/higaad/windows']               = 'Higaad/windows';
$route['admin/higaad/windows/store']         = 'Higaad/store_window';
$route['admin/higaad/submitted']             = 'Higaad/submitted_subjects';
$route['admin/higaad/submitted/view/(:num)'] = 'Higaad/view_submitted_class/$1';
$route['admin/academic_year/save']           = 'Higaad/store_window';

/* ===================== Settings ===================== */
$route['admin/settings'] = 'Higaad/setting';

/* ===================== NEW REST API (Sanabil) ===================== */
// Halkan waa goobta API-gu ka diiwaangashan yahay si uu u furmo:
$route['api/v1/parents/allowed'] = 'Api/allowed_parents';

/* ===================== NEW PARENT TESTING UI (Sanabil) ===================== */
$route['parents'] = 'Parents/index';
