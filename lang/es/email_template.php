<?php

return [

    /*
    |--------------------------------------------------------------------------
    | All Translation Lines For Email Templates
    |--------------------------------------------------------------------------
    */

    'email_templates'                           => 'Plantillas de Correo Electrónico',
    'all_templates'                             => 'Todas las Plantillas de Correo Electrónico',
    'select_template'                           => 'Seleccionar Plantilla',
    'set_email_status'                          => 'Establecer estado del correo como',
    'add_email_template'                        => 'Agregar nueva plantilla',
    'update_email_template'                     => 'Actualizar plantilla de correo',
    'email_title'                               => 'Título del correo',
    'role_type'                                 => 'Tipo de rol',
    'approve_instructor'                        => 'Aprobar Instructor',
    'select_template'                           => 'Seleccionar plantilla',
    'btn_invite'                                => 'Aceptar Invitación',
    'admin'                                     => 'Administrador',
    'verfiy_email'                              => 'Verificar Dirección de Correo Electrónico',
    'login_url'                                 => '"Iniciar Sesión"',
    'ridirect_login'                            => 'Redirigir al inicio de sesión',
    'reset_password_txt'                        => 'Restablecer contraseña',
    'email_setting_variable'                    => 'Variables de configuración de correo',
    'greeting_text'                             => 'Texto de saludo',
    'email_content'                             => 'Contenido del correo',
    'subject'                                   => 'Asunto del correo',

    // =========== Email general translation ==================== \\
    'email_content'                                     => 'Contenido del Correo Electrónico',
    'registration_title'                                => 'Correo de Registro',
    'welcome_title'                                     => 'Correo de Bienvenida',
    'email_verification_title'                          => 'Verificación de Correo Electrónico',
    'password_reset_title'                              => 'Solicitud de Restablecimiento de Contraseña',
    'identity_verification_request_title'               => 'Solicitud de Verificación de Identidad',
    'identity_verification_approved_title'              => 'Verificación de Identidad Aprobada',
    'identity_verification_rejected_title'              => 'Verificación de Identidad Rechazada',
    'session_booking_title'                             => 'Reserva de Sesión',
    'booking_rescheduled_title'                         => 'Reserva Reprogramada',
    'withdraw_wallet_amount_request_title'              => 'Solicitud de Retiro de Monto de Billetera',
    'accepted_withdraw_request_title'                   => 'Solicitud de Retiro Aceptada',
    'new_message_notification_title'                    => 'Notificación de Nuevo Mensaje',
    'booking_link_title'                                => 'Enlace de Reunión para Tu Próxima Sesión',
    'session_request_title'                             => 'Solicitud de Sesión',
    'booking_completion_request_title'                  => 'Solicitud de Finalización de Reserva',

    'variables_used'                                    => 'Variables utilizadas en el correo',
    'subject'                                           => 'Asunto',

    'welcome_student_subject'                           => '¡Bienvenido a Lernen, {userName}!',
    'welcome_tutor_subject'                             => '¡Bienvenido a Lernen, {userName}!',

    'welcome_student_content'                           => 'Estamos encantados de tenerte en nuestra vibrante comunidad de aprendizaje. Ya sea que busques mejorar tus habilidades, explorar nuevas materias o alcanzar tus metas académicas, estamos aquí para apoyarte en cada paso del camino.',
    'welcome_tutor_content'                             => 'Estamos emocionados de tenerte en nuestra comunidad de educadores apasionados. Ya sea que estés aquí para compartir tu experiencia, inspirar estudiantes o hacer crecer tu carrera docente, estamos comprometidos a proporcionarte las herramientas y el apoyo que necesitas para tener éxito.',
    
    'registration_student_subject'                      => '¡Bienvenido a Lernen, {userName}!',
    'registration_tutor_subject'                        => '¡Bienvenido a Lernen, {userName}!',
    'registration_admin_subject'                        => 'Notificación de Registro de Nuevo Usuario',
    
    'greeting'                                          => 'Estimado :userName,',
    'greeting_admin'                                    => 'Estimado Administrador,',
    
    'registration_student_content'                      => '¡Estamos emocionados de tenerte en la comunidad Lernen, :userName! Por favor verifica tu dirección de correo electrónico haciendo clic en el siguiente enlace: :verificationLink Esperamos verte prosperar y alcanzar tus metas de aprendizaje.',
    'registration_tutor_content'                        => '¡Estamos encantados de darte la bienvenida como tutor en Lernen, :userName! Para completar tu registro, por favor verifica tu dirección de correo electrónico haciendo clic en el siguiente enlace: :verificationLink Estamos emocionados de ver el impacto positivo que tendrás en los viajes de aprendizaje de tus estudiantes.',
    
    'registration_admin_content'                        => ':userName se ha registrado con el correo :userEmail. Por favor verifica sus detalles y asegúrate de que tengan una gran experiencia en nuestra plataforma.',

    'email_verification_subject'                        => '¡Verifica Tu Dirección de Correo Electrónico, :userName!',
    'email_verification_content'                        => 'Por favor verifica tu dirección de correo electrónico para completar tu registro. Haz clic en el siguiente enlace: :verificationLink Estamos ansiosos de que comiences tu viaje de aprendizaje con nosotros.',

    'password_reset_subject'                            => 'Solicitud de Restablecimiento de Contraseña',
    'password_reset_content'                            => 'Recibimos una solicitud para restablecer tu contraseña. Si hiciste esta solicitud, por favor haz clic en el siguiente enlace para restablecer tu contraseña: :resetLink Si no solicitaste un restablecimiento de contraseña, por favor ignora este correo.',

    'identity_verification_request_subject'             => 'Verificación de Identidad Requerida',
    'identity_verification_request_content'             => 'Gracias por enviar tu solicitud de verificación de identidad en la plataforma Lernen. Nuestro equipo administrativo revisará tu información en breve.<br> Nombre: {userName} <br> Rol: {userRole} <br> Correo: {userEmail} <br> Fecha de Solicitud: {requestDate} <br> Recibirás un correo de confirmación una vez que tu identidad sea verificada. Si necesitamos detalles adicionales, nos pondremos en contacto contigo directamente.',
    'identity_verification_request_admin_subject'       => 'Nueva Solicitud de Verificación de Identidad',
    'identity_verification_request_admin_content'       => 'Se ha enviado una nueva solicitud de verificación de identidad en la plataforma Lernen. Por favor encuentra los detalles del usuario a continuación: <br> Nombre: {userName} <br> Rol: {userRole} <br> Correo: {userEmail} <br> Fecha de Solicitud: {requestDate} <br> Por favor procede con los pasos necesarios para verificar la identidad de este usuario.',

    'identity_verification_approved_subject'            => 'Verificación de Identidad Aprobada',
    'identity_verification_approved_content'            => '¡Felicidades, :userName! Tu identidad ha sido verificada exitosamente. Ahora puedes disfrutar completamente de todos los beneficios de nuestra plataforma. Gracias por tu cooperación.',

    'identity_verification_rejected_subject'            => 'Verificación de Identidad Rechazada',
    'identity_verification_rejected_content'            => 'Lamentamos informarte, :userName, que tu verificación de identidad ha sido rechazada. Razón: :rejectionReason. Por favor proporciona los documentos requeridos e intenta nuevamente. Si tienes alguna pregunta, no dudes en contactar a nuestro equipo de soporte.',

    'session_booking_student_subject'                   => '¡Gracias por reservar con Lernen!',
    'session_booking_student_content'                   => 'Estamos emocionados de confirmar tus próximas sesiones. Aquí están los detalles de cada una de tus reservas <br> :bookingDetails <br> ¡Gracias por elegir Lernen! Estamos aquí para ayudarte a alcanzar tus metas de aprendizaje.',
    'session_booking_tutor_subject'                     => '¡Gracias por reservar con Lernen!',
    'session_booking_tutor_content'                     => 'Estamos emocionados de confirmar tus próximas sesiones. Aquí están los detalles de cada una de tus reservas <br> :bookingDetails <br> ¡Gracias por elegir Lernen! Estamos aquí para ayudarte a alcanzar tus metas de aprendizaje.',

    'booking_rescheduled_subject'                       => 'Tu Sesión Ha Sido Reprogramada',
    'booking_rescheduled_content'                       => 'Queríamos informarte que tu sesión con :tutorName ha sido reprogramada para :newSessionDate debido a la siguiente razón: <br /> :reason. Puedes ver más detalles haciendo clic en el siguiente enlace :viewLink Nos disculpamos por cualquier inconveniente y agradecemos tu comprensión.',

    'withdraw_wallet_amount_request_subject'            => 'Solicitud de Retiro de Monto de Billetera',
    'withdraw_wallet_amount_request_content'            => ':userName ha solicitado retirar :withdrawAmount de su billetera. Por favor revisa y procesa la solicitud a tu conveniencia.',

    'accepted_withdraw_request_subject'                 => 'Tu Solicitud de Retiro Ha Sido Aprobada',
    'accepted_withdraw_request_content'                 => '¡Buenas noticias, :userName! Tu solicitud para retirar :withdrawAmount de tu billetera ha sido aprobada. El monto será acreditado a tu cuenta en breve. Gracias por ser un miembro valioso de la comunidad Lernen.',

    'new_message_subject'                               => 'Nuevo Mensaje de :messageSender',
    'new_message_content'                               => 'Has recibido un nuevo mensaje de :messageSender. Por favor inicia sesión en tu cuenta para responder.',
    
    'meeting_link_subject'                              => 'Enlace de Reunión para Tu Próxima Sesión',
    'meeting_link_content'                              => 'Espero que este mensaje te encuentre bien. Queríamos informarte que tu tutor ha actualizado el enlace de reunión para tu próxima sesión. Por favor asegúrate de usar el nuevo enlace al unirte.<br /> La sesión está programada para <strong>{sessionDate}</strong> y cubre <strong>{sessionSubject}</strong>. El enlace de reunión actualizado se proporciona aquí: {meetingLink} Por favor usa este enlace para unirte a tu sesión en el horario programado.',
    
    'booking_completion_request_subject'                => 'Confirma Tu Sesión con {tutorName}',
    'booking_completion_request_content'                => 'Tu sesión con {tutorName} para la fecha {sessionDateTime} ha sido completada. Por favor confirma si la sesión fue satisfactoria o será marcada como completada por el sistema automáticamente después de {days} días. <br> {completeBookingLink}',

    'welcome_student_variables'                         => '{userName} - Para Nombre de Usuario <br>',
    'welcome_tutor_variables'                           => '{userName} - Para Nombre de Usuario <br>',
    
    'registration_student_variables'                    => '{userName} - Para Nombre de Usuario <br> {userEmail} - Para Correo del Usuario <br> {verificationLink} - Para Enlace de Verificación',
    'registration_tutor_variables'                      => '{userName} - Para Nombre de Usuario <br> {userEmail} - Para Correo del Usuario <br> {verificationLink} - Para Enlace de Verificación',
    
    'registration_admin_variables'                      => '{userName} - Para Nombre de Usuario <br> {userEmail} - Para Correo del Usuario',

    'email_verification_student_variables'              => '{userName} - Para Nombre de Usuario <br> {verificationLink} - Para Enlace de Verificación',
    'email_verification_tutor_variables'                => '{userName} - Para Nombre de Usuario <br> {verificationLink} - Para Enlace de Verificación',

    'password_reset_student_variables'                  => '{userName} - Para Nombre de Usuario <br> {resetLink} - Para Enlace de Restablecimiento de Contraseña',
    'password_reset_tutor_variables'                    => '{userName} - Para Nombre de Usuario <br> {resetLink} - Para Enlace de Restablecimiento de Contraseña',
    'identity_verification_request_variables'           => '{userName} - Para Nombre de Usuario <br> {userEmail} - Para Correo del Usuario <br> {userRole} - Para Rol del Usuario <br> {requestDate} - Para Fecha de Solicitud del Usuario',

    'identity_verification_approved_student_variables'  => '{userName} - Para Nombre de Usuario',
    'identity_verification_approved_tutor_variables'    => '{userName} - Para Nombre de Usuario',

    'identity_verification_rejected_student_variables'  => '{userName} - Para Nombre de Usuario <br> {rejectionReason} - Para Razón de Rechazo',
    'identity_verification_rejected_tutor_variables'    => '{userName} - Para Nombre de Usuario <br> {rejectionReason} - Para Razón de Rechazo',

    'session_booking_student_variables'                 => '{userName} - Para Nombre de Usuario <br> {sessionDate} - Para Fecha de Sesión <br> {tutorName} - Para Nombre del Tutor <br> {sessionSubject} - Para Materia de la Sesión <br> {bookingDetails} - Para Detalles de las Reservas',
    'session_booking_tutor_variables'                   => '{userName} - Para Nombre de Usuario <br> {sessionDate} - Para Fecha de Sesión <br> {studentName} - Para Nombre del Estudiante <br> {sessionSubject} - Para Materia de la Sesión <br> {bookingDetails} - Para Detalles de las Reservas',

    'booking_rescheduled_student_variables'             => '{userName} - Para Nombre de Usuario <br> {newSessionDate} - Para Nueva Fecha de Sesión <br> {tutorName} - Para Nombre del Tutor <br> {viewLink} - Para Ver Detalle',

    'withdraw_wallet_amount_request_admin_variables'    => '{userName} - Para Nombre de Usuario <br> {withdrawAmount} - Para Monto de Retiro',

    'accepted_withdraw_request_tutor_variables'         => '{userName} - Para Nombre de Usuario <br> {withdrawAmount} - Para Monto de Retiro',
    
    'booking_completion_request_student_variables'      => '{userName} - Para Nombre de Usuario <br> {tutorName} - Para Nombre del Tutor <br> {sessionDateTime} - Para Fecha y Hora de la Sesión <br> {completeBookingLink} - Para Enlace de Completar Reserva <br> {days} - Para Días',

    'new_message_student_variables'                     => '{userName} - Para Nombre de Usuario <br> {messageSender} - Para Remitente del Mensaje',
    'new_message_tutor_variables'                       => '{userName} - Para Nombre de Usuario <br> {messageSender} - Para Remitente del Mensaje',

    'meeting_link_student_variables'                    => '{userName} - Para Nombre de Usuario <br> {sessionDate} - Para Fecha de Sesión <br> {tutorName} - Para Nombre del Tutor <br> {sessionSubject} - Para Materia de la Sesión <br> {meetingLink} - Para Enlace de Reunión',
    'session_request_student_variables'                 => '{userName} - Para Nombre de Usuario <br> {studentName} - Para Nombre del Estudiante <br> {studentEmail} - Para Correo del Estudiante <br> {sessionType} - Para Tipo de Sesión <br> {message} - Para Mensaje',
    'session_request_admin_variables'                   => '{userName} - Para Nombre de Usuario <br> {studentName} - Para Nombre del Estudiante <br> {studentEmail} - Para Correo del Estudiante <br> {sessionType} - Para Tipo de Sesión <br> {message} - Para Mensaje',
    'session_request_subject'                           => 'Nueva Solicitud de Sesión Personalizada de {studentName}',
    'session_request_content'                           => 'Has recibido una nueva solicitud de sesión de un estudiante. Aquí están los detalles:<br /> <br /> <strong> Nombre del Estudiante </strong>: {studentName}<br /><strong> Correo del Estudiante </strong>: {studentEmail}<br ><strong> Tipo de Sesión </strong>: {sessionType}<br /><strong> Mensaje </strong>: {message}',
    'session_request_greeting_admin'                    => 'Hola Administrador,',
    'session_request_subject_admin'                     => 'Nueva Solicitud de Sesión Personalizada Enviada por {studentName}',
    'session_request_content_admin'                     => 'Una nueva solicitud de sesión personalizada ha sido enviada por un estudiante. A continuación están los detalles:<br /> <br /> <strong> Nombre del Estudiante </strong>: {studentName}<br /><strong> Correo del Estudiante </strong>: {studentEmail}<br ><strong> Tipo de Sesión </strong>: {sessionType}<br /><strong> Mensaje </strong>: {message}',

    //dispute email template
    'dispute_title'                                     => 'Razón de Disputa',
    'dispute_student_confirmation_title'                => 'Confirmación de Razón de Disputa',

    'dispute_tutor_variables'                           => '{tutorName} - Para Nombre del Tutor <br> {studentName} - Para Nombre del Estudiante <br> {disputeReason} - Para Razón de la Disputa',
    'dispute_tutor_subject'                             => 'Confirmación de Razón de Disputa para Reserva con {studentName}',
    'dispute_tutor_content'                             => 'Una disputa ha sido levantada por {studentName} con respecto a tu sesión reciente. Hemos puesto el pago en espera mientras revisamos el problema.<br><br>Razón de la Disputa:<br>{disputeReason}<br><br>Por favor inicia sesión en tu panel para ver más detalles y proporcionar tu respuesta si es necesario.<br><br>Saludos cordiales,<br>El Equipo Lernen',
    
    'admin_dispute_tutor_subject'                       => 'Nueva Disputa Enviada para Sesión con {studentName} y {tutorName}',
    'admin_dispute_tutor_variables'                     => '{studentName} - Para Nombre del Estudiante <br> {tutorName} - Para Nombre del Tutor <br> {sessionDateTime} - Para Fecha y Hora de la Sesión <br> {disputeReason} - Para Razón de la Disputa',
    'admin_dispute_tutor_content'                       => 'Una nueva disputa ha sido enviada para la sesión entre {studentName} y {tutorName}. A continuación están los detalles de la disputa:<br><br>Detalles de la Sesión:<br>Tutor: {tutorName}<br>Estudiante: {studentName}<br>Fecha y Hora: {sessionDateTime}<br>Razón de la Disputa: {disputeReason}<br><br>Por favor revisa el caso y toma las medidas apropiadas.<br><br>Saludos cordiales,<br>El Equipo Lernen',
    
    'dispute_resolution_title'                          => 'Notificación de Resolución de Disputa',

    'dispute_resolved_student_subject'                  => 'Disputa Resuelta – Reembolso Emitido para Tu Sesión con :tutorName',
    'dispute_resolved_student_content'                  => "Estimado :studentName,<br>Tu disputa para la sesión con :tutorName ha sido resuelta a tu favor. Un reembolso de :refundAmount ha sido emitido a tu método de pago original.<br>Detalles de la Sesión: <br>Mensaje: :disputeReason <br> Tutor: :tutorName<br>Fecha y Hora: :sessionDateTime<br>Reembolso Emitido: :refundAmount<br><br>Gracias por tu paciencia.<br><br>Saludos cordiales,<br>El Equipo Lernen",
    'dispute_resolve_student_variables'                 => 'Nombres de Variables Posibles: {{studentName}}, {{tutorName}}, {{sessionDateTime}}, {{refundAmount}}, {{disputeReason}}',

    'dispute_resolved_tutor_subject'                    => 'Disputa Resuelta – Pago Liberado para Sesión con :studentName',
    'dispute_resolved_tutor_content'                    => "Estimado :tutorName,<br>La disputa levantada para la sesión con :studentName ha sido resuelta a tu favor. El pago por la sesión ha sido liberado a tu billetera.<br>Detalles de la Sesión: <br>Mensaje: :disputeReason <br>Estudiante: :studentName<br>Fecha y Hora: :sessionDateTime<br>Pago Liberado: :paymentAmount<br><br>Gracias por tu paciencia durante el proceso de revisión.<br><br>Saludos cordiales,<br>El Equipo Lernen",
    'dispute_resolve_tutor_variables'                   => 'Nombres de Variables Posibles: {{tutorName}}, {{studentName}}, {{sessionDateTime}}, {{paymentAmount}}, {{disputeReason}}',

    'quiz_title'                                        => 'Notificación de Quiz',
    'assigned_quiz_student_subject'                     => 'Nueva Asignación de Quiz',
    'assigned_quiz_student_content'                     => "Un nuevo quiz <b>:quizName</b> del tutor <b>:tutorName</b> te ha sido asignado. Por favor haz clic en el siguiente botón para intentar el quiz.",
    'assigned_quiz_student_variables'                   => 'Nombres de Variables Posibles: {{studentName}}, {{tutorName}}, {{quizName}}',

    'reviewed_quiz_student_subject'                     => 'Envío de Quiz del Estudiante',
    'reviewed_quiz_student_content'                     => "Un estudiante ha completado su quiz. Por favor revísalo a tu conveniencia. <br>Detalles del Quiz: <br>Quiz: :quizName <br>Estudiante: :studentName <br>",
    'reviewed_quiz_student_variables'                   => 'Nombres de Variables Posibles: {{tutorName}}, {{studentName}}, {{quizName}}, {{submissionDate}}',

    'quiz_result_student_subject'                       => 'Resultado del Quiz Anunciado',
    'quiz_result_student_content'                       => "Tu resultado del quiz ha sido anunciado. Por favor ve tu resultado a tu conveniencia. 
                                                            <br><br>Detalles del Quiz: <br>Tutor: :tutorName <br>Quiz: :quizName",
    'quiz_result_student_variables'                     => 'Nombres de Variables Posibles: {{tutorName}}, {{studentName}}, {{quizName}}',
];
