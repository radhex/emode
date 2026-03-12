<?php namespace Eprog\Manager\Classes;

use Closure;

class BackendPageMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (
   
            $request->is(config('cms.backendUri').'/*') &&
            method_exists($response, 'getStatusCode') &&
            in_array($response->getStatusCode(), [400, 401, 403, 404, 500, 503])
        ) {
            $status = $response->getStatusCode();

            $msgMap = [
                400 => 'badrequest',
                401 => 'accessdenied',
                403 => 'accessdenied',
                404 => 'notfound',
                500 => 'cantdisplay',
                503 => 'serviceunavailable',
            ];

            $msg  = $msgMap[$status] ?? 'cantdisplay';
            $desc = '';

            if (property_exists($response, 'exception') && $response->exception && config('app.debug')) {
                $desc = $status.' '.$response->exception->getMessage()
                    .'<br>'.$response->exception->getFile().' '.$response->exception->getLine();
            }

            if ($request->ajax() || $request->header('X-OCTOBER-REQUEST-HANDLER')) {
                $ajaxmsg = $desc;//if($desc != "") $ajaxmsg = $response->exception->getMessage(); else $ajaxmsg = $msg; 
                return response(
                    $ajaxmsg,
                    $status
                )->header('Content-Type', 'text/plain');
            }

            return response()->view(
                'eprog.manager::error_page',
                [
                    'msg'  => $msg,
                    'desc' => $desc
                ],
                $status
            );
        }

        return $response;
    }
}
