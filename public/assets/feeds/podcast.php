<?php

	$obj = new podcast(NULL, array('podcast'));
    $podcast = $obj->createFeedChannel();
    echo $podcast;

?>