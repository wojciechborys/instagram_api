<?php

if( ! defined( 'ABSPATH' ) ) exit;

$myClass = new \InstagramApp\Instagram_integration(); ?>

<div class="wrap">
    <h1>Instagram</h1>
    <p>Strona służąca do pobierania nowych postów z instagrama</p>
    <p><a href="<?php echo esc_url( $myClass->instagram_oauth_url_call ) ?>" class="button button-primary">Pobierz nowe zdjęcia</a></p>
    <?php $instagram_photos_ids = get_option('instagram_photos_ids');
       foreach($instagram_photos_ids as $photo): ?>
        <img style="width: 200px; height: auto" src="<?php echo $myClass->getImageByTitle($photo) ?>">
    <?php endforeach; ?>
</div>
