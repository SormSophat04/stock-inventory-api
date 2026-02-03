<?php
echo "PHP Version: " . phpversion() . "\n";
echo "pgsql loaded: " . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n";
echo "pdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
