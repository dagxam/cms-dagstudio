<?php
declare(strict_types=1);
/** Управляемые сведения оператора. Пустые реквизиты не публикуются как реальные. */
function cms_privacy(): array {
    $settings=[
        'operator'=>trim(config_value('privacy_operator','')),
        'address'=>trim(config_value('privacy_address','')),
        'email'=>trim(config_value('privacy_email',config_value('contact_email',''))),
        'purpose'=>trim(config_value('privacy_purpose','Рассмотрение и ответ на обращения, поступающие через форму сайта.')),
        'retention'=>trim(config_value('privacy_retention','До достижения цели обработки либо отзыва согласия, если дальнейшее хранение не требуется законом.')),
        'processors'=>trim(config_value('privacy_processors','Данные хранятся в базе данных хостинга сайта.')),
        'version'=>config_value('privacy_version','2026-10-10'),
        'published'=>config_value('privacy_published','0')==='1',
    ];
    $settings['ready']=$settings['published'] &&
        $settings['operator']!=='' && $settings['address']!=='' &&
        filter_var($settings['email'],FILTER_VALIDATE_EMAIL)!==false &&
        $settings['purpose']!=='' && $settings['retention']!=='';
    return $settings;
}
function cms_privacy_url(): string {
    if(cms_privacy()['ready'])return '/privacy.php';
    $existing=config_value('privacy_url','');
    return $existing!=='' && safe_template_url($existing)?$existing:'';
}
function cms_consent_url(): string { return '/consent.php'; }
function cms_privacy_links(): string {
    $url=cms_privacy_url();
    if($url==='')return '';
    return '<nav class="cms-legal-links" aria-label="Конфиденциальность">'
        .'<a href="'.h($url).'">Политика обработки персональных данных</a>'
        .'<a href="/consent.php">Согласие на обработку данных</a>'
        .'<button type="button" data-privacy-open>Настройки конфиденциальности</button></nav>';
}
function cms_consent_table(): void {
    database()->exec("CREATE TABLE IF NOT EXISTS cms_message_consents (
        message_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        consent_version VARCHAR(50) NOT NULL,
        consent_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        consent_text_hash CHAR(64) NOT NULL,
        FOREIGN KEY(message_id) REFERENCES messages(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function cms_consent_text(array $privacy): string {
    return 'Согласие на обработку персональных данных. Оператор: '.$privacy['operator'].
        '. Цель: '.$privacy['purpose'].'. Данные: имя, адрес электронной почты, текст обращения. '.
        'Действия: получение, запись, систематизация, хранение, использование для ответа и удаление. '.
        'Срок: '.$privacy['retention'].'. Отзыв: '.$privacy['email'].'.';
}
function cms_cookie_controls(): string {
    return '<aside class="cms-privacy-banner" data-privacy-banner role="region" aria-labelledby="cms-privacy-title" hidden>'.
      '<div><strong id="cms-privacy-title">Конфиденциальность</strong>'.
      '<p>Сайт использует необходимый сеансовый cookie для работы форм и входа. Видео VK и Rutube могут получать данные посетителя при включённом воспроизведении. Внешние видео не загружаются без вашего выбора.</p>'.
      '<a href="'.h(cms_privacy_url()?:'/privacy.php').'">Политика обработки данных</a></div>'.
      '<div class="cms-privacy-actions"><button type="button" data-privacy-reject>Только необходимые</button>'.
      '<button type="button" data-privacy-accept>Разрешить внешнее видео</button></div>'.
      '</aside>';
}
