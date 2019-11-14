<?php

declare(strict_types=1);

print "<pre>\n";
print_r(json_decode(base64_decode(getenv('PLATFORM_RELATIONSHIPS'))));
print "</pre>\n";

