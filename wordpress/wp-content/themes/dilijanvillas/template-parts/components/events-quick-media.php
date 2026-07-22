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
?>
<a class="events-quick__media" href="<?php echo esc_url($link); ?>">
  <?php if ($media_type === 'video') : ?>
    <video
      class="events-quick__video"
      muted
      loop
      playsinline
      webkit-playsinline
      autoplay
      preload="none"
      disablepictureinpicture
      disableremoteplayback
      aria-hidden="true"
      <?php echo $media_poster !== '' ? 'poster="' . esc_url($media_poster) . '"' : ''; ?>
    >
      <?php /* data-src: JS подставит настоящий src после window.load, до этого видна картинка-постер */ ?>
      <source data-src="<?php echo esc_url($media_url); ?>" type="<?php echo esc_attr($media_mime !== '' ? $media_mime : dilijanvillas_get_video_mime_from_url($media_url)); ?>" />
    </video>
  <?php else : ?>
    <img src="<?php echo esc_url($media_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy" />
  <?php endif; ?>
</a>
