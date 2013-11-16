<?php if (isset($error)) { ?>

<div class="warning">Eway Payment Error: <?php echo $error; ?></div>

<?php } else { ?>

  <?php if (isset($text_testing)) { ?>
  <div class="warning"><?php echo $text_testing; ?></div>
  <?php } ?>

<div class="buttons">
    <div class="right"><a href="<?php echo $action; ?>" class="button"><span><?php echo $button_confirm; ?></span></a></div>
</div>

<?php } ?>
