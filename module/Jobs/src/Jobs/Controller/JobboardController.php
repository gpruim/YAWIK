<?php
/**
 * YAWIK
 *
 * @filesource
 * @copyright (c) 2013-2015 Cross Solution (http://cross-solution.de)
 * @license   MIT
 */

/** ActionController of Jobs */
namespace Jobs\Controller;

use Jobs\Form\ListFilter;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as Session;
use Jobs\Repository;

/**
 * Controller for jobboard actions
 */
class JobboardController extends AbstractActionController
{
    /**
     * @var Repository\Job $jobRepository
     */
    private $jobRepository;

    /**
     * Formular for searching job postings
     *
     * @var ListFilter $searchForm
     */
    private $searchForm;

    /**
     * Construct the jobboard controller
     *
     * @param Repository\Job $jobRepository
     * @param ListFilter $searchForm
     */
    public function __construct(Repository\Job $jobRepository, ListFilter $searchForm)
    {
        $this->jobRepository = $jobRepository;
        $this->searchForm = $searchForm;
    }
    /**
     * attaches further Listeners for generating / processing the output
     * @return $this
     */
    public function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        $serviceLocator = $this->getServiceLocator();
        $defaultServices = $serviceLocator->get('DefaultListeners');
        $events          = $this->getEventManager();
        $events->attach($defaultServices);
        return $this;
    }

    /**
     * List jobs
     */
    public function indexAction()
    {
        $request          = $this->getRequest();
        $params           = $request->getQuery();
        $jsonFormat       = 'json' == $params->get('format');
        $event            = $this->getEvent();
        $routeMatch       = $event->getRouteMatch();
        $matchedRouteName = $routeMatch->getMatchedRouteName();
        $url              = $this->url()->fromRoute($matchedRouteName, array(), array('force_canonical' => true));
        $action           = $routeMatch->getParam('action');
        
        if (!$jsonFormat && !$this->getRequest()->isXmlHttpRequest()) {
            $session = new Session('Jobs\Index');
            $sessionKey = $this->auth()->isLoggedIn() ? 'userParams' : 'guestParams';
            $sessionParams = $session[$sessionKey];
            if ($sessionParams) {
                foreach ($sessionParams as $key => $value) {
                    $params->set($key, $params->get($key, $value));
                }
            }
            $session[$sessionKey] = $params->toArray();

            $this->searchForm->bind($params);
        }

        if (!isset($params['sort'])) {
            $params['sort']='-date';
        }

        $this->searchForm->setAttribute('action', $url);
        $params['by'] = "guest";
        $paginator = $this->paginator('Jobs/Job',$params);

        $options = $this->searchForm->getOptions();
        $options['showButtons'] = false;
        $this->searchForm->setOptions($options);
        
        $return = array(
            'by' => $params->get('by', 'all'),
            'jobs' => $paginator,
            'filterForm' => $this->searchForm
        );
        return $return;
     }
}
