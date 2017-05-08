<?php

header( 'Content-Type: text/plain; charset=' . \get_option( 'blog_charset' ), true );
header( 'Content-Disposition: attachment; filename="' . $data['filename'] . '"' );
header( 'Content-Length:' . strlen( $data['content'] ) );
header( 'Cache-Control: public, must-revalidate, max-age=0' );
header( 'Pragma: public' );
header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
echo $data['content'];
exit();