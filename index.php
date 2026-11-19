<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_logged_in()) {
    redirect('menu.php');
}
page_header('Grunt');
?>
<img class="scene-img" src="images/alley.svg" width="800" height="450" alt="A rainy alley with balconies and a man in a suit sheltering under one">
<p>This district feeds off the blood of the weak. Today you set off to change your life, and the choices you make, and what you carry, decide how it ends.</p>
<p>Seven places. Four choices at each. Three endings. Mature themes: violence and crime.</p>
<p class="actions">
  <a class="button" href="login.php">Log in</a>
  <a class="button secondary" href="register.php">Create an account</a>
</p>
<?php page_footer();
