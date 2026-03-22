<?php
/**
 * Countdown Block Template
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

$target_date = $attributes['targetDate'] ?? '';
$show_seconds = $attributes['showSeconds'] ?? false;
$alignment = $attributes['alignment'] ?? 'center';

if (empty($target_date)) {
    echo '<div class="countdown-placeholder"><p>' . esc_html__('Please select a target date', 'goodblocks') . '</p></div>';
    return;
}

$target_timestamp = strtotime($target_date);
$current_timestamp = current_time('timestamp');
$difference = $target_timestamp - $current_timestamp;

if ($difference <= 0) {
    echo '<div class="countdown-finished"><p>' . esc_html__('Time\'s up!', 'goodblocks') . '</p></div>';
    return;
}

$days = floor($difference / (60 * 60 * 24));
$hours = floor(($difference % (60 * 60 * 24)) / (60 * 60));
$minutes = floor(($difference % (60 * 60)) / 60);
$seconds = floor($difference % 60);

$block_wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'countdown-container'
]);
?>

<div <?php echo $block_wrapper_attributes; ?>>
    <div
        class="countdown-display countdown-align-<?php echo esc_attr($alignment); ?>"
        data-target-date="<?php echo esc_attr($target_date); ?>"
        data-show-seconds="<?php echo $show_seconds ? 'true' : 'false'; ?>"
    >
        <div class="countdown-unit countdown-days">
            <div class="countdown-number"><?php echo str_pad($days, 2, '0', STR_PAD_LEFT); ?></div>
            <div class="countdown-label"><?php esc_html_e('Days', 'goodblocks'); ?></div>
        </div>

        <div class="countdown-unit countdown-hours">
            <div class="countdown-number"><?php echo str_pad($hours, 2, '0', STR_PAD_LEFT); ?></div>
            <div class="countdown-label"><?php esc_html_e('Hours', 'goodblocks'); ?></div>
        </div>

        <div class="countdown-unit countdown-minutes">
            <div class="countdown-number"><?php echo str_pad($minutes, 2, '0', STR_PAD_LEFT); ?></div>
            <div class="countdown-label"><?php esc_html_e('Minutes', 'goodblocks'); ?></div>
        </div>

        <?php if ($show_seconds): ?>
        <div class="countdown-unit countdown-seconds">
            <div class="countdown-number"><?php echo str_pad($seconds, 2, '0', STR_PAD_LEFT); ?></div>
            <div class="countdown-label"><?php esc_html_e('Seconds', 'goodblocks'); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
