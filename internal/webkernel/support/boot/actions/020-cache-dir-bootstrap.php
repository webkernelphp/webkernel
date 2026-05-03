<?php declare(strict_types=1);

is_dir(WEBKERNEL_CACHE_PATH) || @mkdir(WEBKERNEL_CACHE_PATH, 0750, true);
