<?php
$alert = getMessage();
if ($alert): 
?>
<div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
    <?php echo $alert['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
