<?php
/**
 * Events quick card media (image or video).
 *
 * @package dilijanvillas
 *
 * Expected $args:
 * - link (string)
 * - media (array{type,url,mime,poster})
 * - alt (string)
 */

$link = isset($args['link']) ? (string) $args['link'] : '#';
$media = isset($args['media']) && is_array($args['media']) ? $args['media'] : array();
$alt = isset($args['alt']) ? (string) $args['alt'] : '';

$media_url = isset($media['url']) ? trim((string) $media['url']) : '';
$media_type = isset($media['type']) ? (string) $media['type'] : '';
$media_mime = isset($media['mime']) ? (string) $media['mime'] : '';
$media_poster = isset($media['poster']) ? trim((string) $media['poster']) : '';
if ($media_poster === '' && isset($args['poster'])) {
    $media_poster = trim((string) $args['poster']);
}

if ($media_url === '') {
    return;
}

if ($media_type === '' && function_exists('dilijanvillas_is_video_media')) {
    $media_type = dilijanvillas_is_video_media($media_url, $media_mime) ? 'video' : 'image';
}

/**
 * Видео на главной слишком тяжёлое — показываем только картинку.
 * Для видео это его постер (beground_img). Если постера нет, карточку не рисуем.
 */
$image_url = $media_type === 'video' ? $media_poster : $media_url;
if ($image_url === '') {
    return;
}
?>
<a class="events-quick__media" href="<?php echo esc_url($link); ?>">
  <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" />
</a>
