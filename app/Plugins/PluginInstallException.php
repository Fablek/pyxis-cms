<?php

namespace App\Plugins;

use RuntimeException;

/**
 * Thrown when an uploaded plugin archive cannot be installed. The message is translated and safe to show in the panel.
 */
class PluginInstallException extends RuntimeException {}
