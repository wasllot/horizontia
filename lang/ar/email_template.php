<?php

return [

    /*
    |--------------------------------------------------------------------------
    | All Translation Lines For Email Templates
    |--------------------------------------------------------------------------
    */

    'email_templates'                           => 'قوالب البريد الإلكتروني',
    'all_templates'                             => 'جميع قوالب البريد الإلكتروني',
    'select_template'                           => 'اختر القالب',
    'set_email_status'                          => 'تعيين حالة البريد الإلكتروني كـ',
    'add_email_template'                        => 'إضافة قالب جديد',
    'update_email_template'                     => 'تحديث قالب البريد الإلكتروني',
    'email_title'                               => 'عنوان البريد الإلكتروني',
    'role_type'                                 => 'نوع الدور',
    'approve_instructor'                        => 'الموافقة على المدرب',
    'select_template'                           => 'اختر القالب',
    'btn_invite'                                => 'قبول الدعوة',
    'admin'                                     => 'مسؤول',
    'verfiy_email'                              => 'تأكيد عنوان البريد الإلكتروني',
    'login_url'                                 => '“تسجيل الدخول”',
    'ridirect_login'                            => 'إعادة التوجيه إلى تسجيل الدخول',
    'reset_password_txt'                        => 'إعادة تعيين كلمة المرور',
    'email_setting_variable'                    => 'متغيرات إعداد البريد الإلكتروني',
    'greeting_text'                             => 'نص التحية',
    'email_content'                             => 'محتوى البريد الإلكتروني',
    'subject'                                   => 'موضوع البريد الإلكتروني',

    // =========== Email general translation ==================== \\
    'email_content'                                     => 'محتوى البريد الإلكتروني',
    'registration_title'                                => 'بريد التسجيل',
    'email_verification_title'                          => 'التحقق من البريد الإلكتروني',
    'password_reset_title'                              => 'طلب إعادة تعيين كلمة المرور',
    'identity_verification_request_title'               => 'طلب التحقق من الهوية',
    'identity_verification_approved_title'              => 'تمت الموافقة على التحقق من الهوية',
    'identity_verification_rejected_title'              => 'تم رفض التحقق من الهوية',
    'session_booking_title'                             => 'حجز الجلسة',
    'booking_rescheduled_title'                         => 'إعادة جدولة الحجز',
    'withdraw_wallet_amount_request_title'              => 'طلب سحب مبلغ من المحفظة',
    'accepted_withdraw_request_title'                   => 'تم قبول طلب السحب',
    'new_message_notification_title'                    => 'إشعار رسالة جديدة',
    'booking_link_title'                                => 'رابط الاجتماع لجلسة القادمة',
    'session_request_title'                             => 'طلب جلسة',
    'booking_completion_request_title'                  => 'طلب إكمال الحجز',

    'variables_used'                                    => 'المتغيرات المستخدمة في البريد الإلكتروني',
    'subject'                                           => 'الموضوع',

    'registration_student_subject'                      => 'مرحبًا بك في ليرنن، {userName}!',
    'registration_tutor_subject'                        => 'مرحبًا بك في ليرنن، {userName}!',
    'registration_admin_subject'                        => 'إشعار تسجيل مستخدم جديد',
    'greeting'                                          => 'عزيزي :userName،',
    'greeting_admin'                                    => 'عزيزي المسؤول،',

    'registration_student_content'                      => 'نحن متحمسون لانضمامك إلى مجتمع ليرنن، :userName! يرجى التحقق من عنوان بريدك الإلكتروني بالنقر على الرابط التالي: :verificationLink نتطلع لرؤيتك تحقق أهدافك التعليمية.',
    'registration_tutor_content'                        => 'نحن سعداء بترحيبك كمدرب في ليرنن، :userName! لإكمال تسجيلك، يرجى التحقق من عنوان بريدك الإلكتروني بالنقر على الرابط التالي: :verificationLink نحن متحمسون لرؤية التأثير الإيجابي الذي ستحدثه على رحلات تعلم طلابك.',
    'registration_admin_content'                        => ':userName قد سجل باستخدام البريد الإلكتروني :userEmail. يرجى التحقق من تفاصيلهم وضمان حصولهم على تجربة رائعة على منصتنا.',

    'email_verification_subject'                        => 'تحقق من عنوان بريدك الإلكتروني، :userName!',
    'email_verification_content'                        => 'يرجى التحقق من عنوان بريدك الإلكتروني لإكمال تسجيلك. انقر على الرابط التالي: :verificationLink نحن متحمسون لبدء رحلتك التعليمية معنا.',

    'password_reset_subject'                            => 'طلب إعادة تعيين كلمة المرور',
    'password_reset_content'                            => 'لقد تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بك. إذا قمت بهذا الطلب، يرجى النقر على الرابط التالي لإعادة تعيين كلمة المرور: :resetLink إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.',

    'identity_verification_request_subject'             => 'مطلوب التحقق من الهوية',
    'identity_verification_request_content'             => 'شكرًا لتقديم طلب التحقق من الهوية على منصة ليرنن. سيقوم فريق الإدارة بمراجعة معلوماتك قريبًا.<br> الاسم: {userName} <br> الدور: {userRole} <br> البريد الإلكتروني: {userEmail} <br> تاريخ الطلب: {requestDate} <br> ستتلقى بريدًا إلكترونيًا للتأكيد بمجرد التحقق من هويتك. إذا كنا بحاجة إلى أي تفاصيل إضافية، سنتواصل معك مباشرة.',
    'identity_verification_request_admin_subject'       => 'طلب تحقق من الهوية جديد',
    'identity_verification_request_admin_content'       => 'تم تقديم طلب تحقق من الهوية جديد على منصة ليرنن. يرجى العثور على تفاصيل المستخدم أدناه: <br> الاسم: {userName} <br> الدور: {userRole} <br> البريد الإلكتروني: {userEmail} <br> تاريخ الطلب: {requestDate} <br> يرجى اتخاذ الخطوات اللازمة للتحقق من هوية هذا المستخدم.',

    'identity_verification_approved_subject'            => 'تمت الموافقة على التحقق من الهوية',
    'identity_verification_approved_content'            => 'تهانينا، :userName! تم التحقق من هويتك بنجاح. يمكنك الآن الاستمتاع بجميع مزايا منصتنا. شكرًا لتعاونك.',

    'identity_verification_rejected_subject'            => 'تم رفض التحقق من الهوية',
    'identity_verification_rejected_content'            => 'نأسف لإبلاغك، :userName، بأن التحقق من هويتك قد تم رفضه. السبب: :rejectionReason. يرجى تقديم المستندات المطلوبة والمحاولة مرة أخرى. إذا كان لديك أي أسئلة، لا تتردد في الاتصال بفريق الدعم لدينا.',

    'session_booking_student_subject'                   => 'شكرًا لحجزك مع ليرنن!',
    'session_booking_student_content'                   => 'نحن متحمسون لتأكيد جلساتك القادمة. إليك تفاصيل كل حجز <br> :bookingDetails <br> شكرًا لاختيارك ليرنن! نحن هنا لمساعدتك في تحقيق أهدافك التعليمية.',
    'session_booking_tutor_subject'                     => 'شكرًا لحجزك مع ليرنن!',
    'session_booking_tutor_content'                     => 'نحن متحمسون لتأكيد جلساتك القادمة. إليك تفاصيل كل حجز <br> :bookingDetails <br> شكرًا لاختيارك ليرنن! نحن هنا لمساعدتك في تحقيق أهدافك التعليمية.',

    'booking_rescheduled_subject'                       => 'تم إعادة جدولة جلستك',
    'booking_rescheduled_content'                       => 'نود إبلاغك بأن جلستك مع :tutorName قد تم إعادة جدولتها إلى :newSessionDate بسبب السبب التالي: <br /> :reason. يمكنك عرض المزيد من التفاصيل بالنقر على الرابط التالي :viewLink نعتذر عن أي إزعاج ونقدر تفهمك.',

    'withdraw_wallet_amount_request_subject'            => 'طلب سحب مبلغ من المحفظة',
    'withdraw_wallet_amount_request_content'            => ':userName قد طلب سحب :withdrawAmount من محفظته. يرجى مراجعة ومعالجة الطلب في أقرب وقت ممكن.',

    'accepted_withdraw_request_subject'                 => 'تمت الموافقة على طلب السحب الخاص بك',
    'accepted_withdraw_request_content'                 => 'أخبار جيدة، :userName! تم الموافقة على طلبك لسحب :withdrawAmount من محفظتك. سيتم إضافة المبلغ إلى حسابك قريبًا. شكرًا لكونك عضوًا قيمًا في مجتمع ليرنن.',

    'new_message_subject'                               => 'رسالة جديدة من :messageSender',
    'new_message_content'                               => 'لقد تلقيت رسالة جديدة من :messageSender. يرجى تسجيل الدخول إلى حسابك للرد.',

    'meeting_link_subject'                              => 'رابط الاجتماع لجلسة القادمة',
    'meeting_link_content'                              => 'نأمل أن تكون هذه الرسالة قد وصلتك وأنت بخير. نود إبلاغك بأن مدربك قد قام بتحديث رابط الاجتماع لجلسة القادمة. يرجى التأكد من استخدام الرابط الجديد عند الانضمام.<br /> الجلسة مجدولة في <strong>{sessionDate}</strong> وتغطي <strong>{sessionSubject}</strong>. الرابط المحدث للاجتماع مرفق هنا: {meetingLink} يرجى استخدام هذا الرابط للانضمام إلى جلستك في الوقت المحدد.',

    'booking_completion_request_subject'               => 'تأكيد جلستك مع {tutorName}',
    'booking_completion_request_content'               => 'جلستك مع {tutorName} بتاريخ {sessionDateTime} قد اكتملت. يرجى تأكيد ما إذا كانت الجلسة مرضية أو سيتم اعتبارها مكتملة تلقائيًا من قبل النظام بعد {days} أيام. <br> {completeBookingLink}',

    'registration_student_variables'                    => '{userName} - لاسم المستخدم <br> {userEmail} - لبريد المستخدم <br> {verificationLink} - لرابط التحقق',
    'registration_tutor_variables'                      => '{userName} - لاسم المستخدم <br> {userEmail} - لبريد المستخدم <br> {verificationLink} - لرابط التحقق',
    'registration_admin_variables'                      => '{userName} - لاسم المستخدم <br> {userEmail} - لبريد المستخدم',

    'email_verification_student_variables'              => '{userName} - لاسم المستخدم <br> {verificationLink} - لرابط التحقق',
    'email_verification_tutor_variables'                => '{userName} - لاسم المستخدم <br> {verificationLink} - لرابط التحقق',

    'password_reset_student_variables'                  => '{userName} - لاسم المستخدم <br> {resetLink} - لرابط إعادة تعيين كلمة المرور',
    'password_reset_tutor_variables'                    => '{userName} - لاسم المستخدم <br> {resetLink} - لرابط إعادة تعيين كلمة المرور',
    'identity_verification_request_variables'           => '{userName} - لاسم المستخدم <br> {userEmail} - لبريد المستخدم <br> {userRole} - لدور المستخدم <br> {requestDate} - لتاريخ طلب المستخدم',

    'identity_verification_approved_student_variables'  => '{userName} - لاسم المستخدم',
    'identity_verification_approved_tutor_variables'    => '{userName} - لاسم المستخدم',

    'identity_verification_rejected_student_variables'  => '{userName} - لاسم المستخدم <br> {rejectionReason} - لسبب الرفض',
    'identity_verification_rejected_tutor_variables'    => '{userName} - لاسم المستخدم <br> {rejectionReason} - لسبب الرفض',

    'session_booking_student_variables'                 => '{userName} - لاسم المستخدم <br> {sessionDate} - لتاريخ الجلسة <br> {tutorName} - لاسم المدرب <br> {sessionSubject} - لموضوع الجلسة <br> {bookingDetails} - لتفاصيل الحجوزات',
    'session_booking_tutor_variables'                   => '{userName} - لاسم المستخدم <br> {sessionDate} - لتاريخ الجلسة <br> {studentName} - لاسم الطالب <br> {sessionSubject} - لموضوع الجلسة <br> {bookingDetails} - لتفاصيل الحجوزات',

    'booking_rescheduled_student_variables'             => '{userName} - لاسم المستخدم <br> {newSessionDate} - لتاريخ الجلسة الجديد <br> {tutorName} - لاسم المدرب <br> {viewLink} - لعرض التفاصيل',

    'withdraw_wallet_amount_request_admin_variables'    => '{userName} - لاسم المستخدم <br> {withdrawAmount} - لمبلغ السحب',

    'accepted_withdraw_request_tutor_variables'         => '{userName} - لاسم المستخدم <br> {withdrawAmount} - لمبلغ السحب',

    'accepted_withdraw_request_tutor_variables'         => '{userName} - لاسم المستخدم <br> {withdrawAmount} - لمبلغ السحب',

    'booking_completion_request_student_variables'      => '{userName} - لاسم المستخدم <br> {tutorName} - لاسم المدرب <br> {sessionDateTime} - لتاريخ ووقت الجلسة <br> {completeBookingLink} - لرابط إكمال الحجز <br> {days} - للأيام',

    'new_message_student_variables'                     => '{userName} - لاسم المستخدم <br> {messageSender} - لمرسل الرسالة',
    'new_message_tutor_variables'                       => '{userName} - لاسم المستخدم <br> {messageSender} - لمرسل الرسالة',

    'meeting_link_student_variables'                    => '{userName} - لاسم المستخدم <br> {sessionDate} - لتاريخ الجلسة <br> {tutorName} - لاسم المدرب <br> {sessionSubject} - لموضوع الجلسة <br> {meetingLink} - لرابط الاجتماع',
    'session_request_student_variables'                 => '{userName} - لاسم المستخدم <br> {studentName} - لاسم الطالب <br> {studentEmail} - لبريد الطالب <br> {sessionType} - لنوع الجلسة <br> {message} - للرسالة',
    'session_request_admin_variables'                   => '{userName} - لاسم المستخدم <br> {studentName} - لاسم الطالب <br> {studentEmail} - لبريد الطالب <br> {sessionType} - لنوع الجلسة <br> {message} - للرسالة',
    'session_request_subject'                           => 'طلب جلسة مخصصة جديدة من {studentName}',
    'session_request_content'                           => 'لقد تلقيت طلب جلسة جديدة من طالب. إليك التفاصيل:<br /> <br /> <strong> اسم الطالب </strong>: {studentName}<br /><strong> بريد الطالب </strong>: {studentEmail}<br ><strong> نوع الجلسة </strong>: {sessionType}<br /><strong> الرسالة </strong>: {message}',
    'session_request_greeting_admin'                    => 'مرحبًا مسؤول،',
    'session_request_subject_admin'                     => 'تم تقديم طلب جلسة مخصصة جديدة من {studentName}',
    'session_request_content_admin'                     => 'تم تقديم طلب جلسة مخصصة جديدة من طالب. فيما يلي التفاصيل:<br /> <br /> <strong> اسم الطالب </strong>: {studentName}<br /><strong> بريد الطالب </strong>: {studentEmail}<br ><strong> نوع الجلسة </strong>: {sessionType}<br /><strong> الرسالة </strong>: {message}',
];
