<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_logged_in()) {
    redirect('menu.php');
}
page_header('Veilbound');
?>
<img class="scene-img" src="images/rooftop.svg" width="800" height="450" alt="City skyline at dusk seen from a rooftop">
<p>The seal on the Meridian Arcade has broken, and Hollows are leaking into the city. You are a rookie sorcerer of the Lantern Order, and the choices you make, and the items you carry, decide what happens next.</p>
<p>Nine pages. Four choices on each. Three possible endings.</p>
<p class="actions">
  <a class="button" href="login.php">Log in</a>
  <a class="button secondary" href="register.php">Create an account</a>
</p>
<?php page_footer();
