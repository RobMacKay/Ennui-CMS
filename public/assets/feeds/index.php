<?php

    // DB Info
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);

    // Set the site name
  	$siteFull = SITE_NAME;
    $siteName = SITE_URL;
    $siteDesc = SITE_DESCRIPTION;

    $xml=<<<________EOD
<?xml version="1.0"?>
<rss version="2.0">
  <channel>

    <title>$siteFull</title>
    <link>$siteName</link>
    <description>
      $siteDesc
    </description>
    <language>en-us</language>

________EOD;

    $sql = "SELECT `title`, `img`, `imgcap`, `body`, `data6`
    		FROM `".DB_PREFIX."entryMgr`
    		WHERE page='blog'
    		ORDER BY `created` DESC
    		LIMIT 15";
    foreach($db->query($sql) as $a) {
      $title = htmlentities(stripslashes($a['title']), ENT_QUOTES, NULL, FALSE);
      $urltitle = isset($a['data6']) ? $a['data6'] : urlencode($a['title']);

      $para = stripslashes($a['body']);
      if (!empty($a['img']))
        $desc = "<img src=\"$siteName$a[img]\" alt=\"$a[imgcap]\" /><br />";
      else
        $desc = "";
      $desc .= $para;
      $desc .= "<p>(To read and post comments for this entry, visit ";
      $desc .="<a href=\"{$siteName}blog/$urltitle\">";
      $desc .="$siteName</a>)</p><hr />";
      $desc = htmlentities($desc, ENT_QUOTES);
      $xml.=<<<____________EOD

    <item>
      <title>$title</title>
      <description>$desc</description>
      <link>{$siteName}blog/$urltitle</link>
      <guid>{$siteName}blog/$urltitle</guid>
    </item>
____________EOD;
    }

    $xml.=<<<________EOD

  </channel>

</rss>
________EOD;

    print_r(stripslashes($xml));
    exit;
