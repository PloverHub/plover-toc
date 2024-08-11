<?php
$injected_args = $args;
$toc_content = $json_ld = '';
if (isset($injected_args['toc'])) {
    $toc_content = $injected_args['toc'];
}
if (isset($injected_args['json_ld'])) {
    $json_ld = $injected_args['json_ld'];
}

// Apply the filter to allow overriding the summary text
$summary_text = apply_filters('plover_toc_summary_text', __('Open Table of contents', 'plover-toc'));
?>
<div class="plover-toc-wrapper">
    <details>
        <summary><?php echo esc_html($summary_text); ?></summary>
        <div class='plover-toc-items'>
            <?php echo wp_kses_post($toc_content); ?>
        </div>
    </details>
</div>
<?php if (!empty($json_ld)) : ?>
    <script type="application/ld+json" class="plover-toc-schema"><?php echo $json_ld; //The JSON-LD content is safely encoded when created, so it does not need further escaping here?></script>
<?php endif; ?>
