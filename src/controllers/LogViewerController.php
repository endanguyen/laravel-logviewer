<?php
namespace EndaLogView\LaravelLogViewer;

use Illuminate\Support\Facades\Crypt;

if (class_exists('\\Illuminate\\Routing\\Controller')) {
    class BaseController extends \Illuminate\Routing\Controller
    {
    }
} elseif (class_exists('Laravel\\Lumen\\Routing\\Controller')) {
    class BaseController extends \Laravel\Lumen\Routing\Controller
    {
    }
}

/**
 * Class LogViewerController.
 */
class LogViewerController extends BaseController
{
    protected $request;

    /**
     * LogViewerController constructor.
     */
    public function __construct()
    {
        $this->request = app('request');
    }

    /**
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function index()
    {
        if ($this->request->input('l')) {
            LaravelLumenLogViewer::setFile(Crypt::decrypt($this->request->input('l')));
        }

        if ($early_return = $this->earlyReturn()) {
            return $early_return;
        }

        $data = [
            'logs'           => LaravelLumenLogViewer::all(),
            'files'          => LaravelLumenLogViewer::getFiles(true),
            'current_file'   => LaravelLumenLogViewer::getFileName(),
            'standardFormat' => true,
        ];

        if ($this->request->wantsJson()) {
            return $data;
        }

        $firstLog = reset($data['logs']);
        if (!$firstLog['context'] && !$firstLog['level']) {
            $data['standardFormat'] = false;
        }

        return app('view')->make('laravel-logviewer::log', $data);
    }

    /**
     * @return bool|mixed
     *
     * @throws \Exception
     */
    private function earlyReturn()
    {
        if ($this->request->input('dl')) {
            return $this->download($this->pathFromInput('dl'));
        } elseif ($this->request->has('clean')) {
            app('files')->put($this->pathFromInput('clean'), '');

            return $this->redirect($this->request->url());
        } elseif ($this->request->has('del')) {
            app('files')->delete($this->pathFromInput('del'));

            return $this->redirect($this->request->url());
        } elseif ($this->request->has('delall')) {
            foreach (LaravelLumenLogViewer::getFiles(true) as $file) {
                app('files')->delete(LaravelLumenLogViewer::pathToLogFile($file));
            }

            return $this->redirect($this->request->url());
        }

        return false;
    }

    /**
     * @param string $input_string
     *
     * @return string
     *
     * @throws \Exception
     */
    private function pathFromInput($input_string)
    {
        return LaravelLumenLogViewer::pathToLogFile(Crypt::decrypt($this->request->input($input_string)));
    }

    /**
     * @param $to
     *
     * @return mixed
     */
    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return redirect($to);
        }

        return app('redirect')->to($to);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }

        // For laravel 4.2
        return app('\Illuminate\Support\Facades\Response')->download($data);
    }
}
