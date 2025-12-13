<?php

namespace App\Api\Controller;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\UI\Controller\IController;

#[Path('/api')]
abstract class BaseController implements IController
{

}