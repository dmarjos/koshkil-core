<?php
namespace Koshkil\Core\Events\Traits;

trait EventManagerTrait {

    private $events=array();

    public function loadClassEvents() {
        $methods=get_class_methods($this);
        foreach($methods as $methodName) {
            $__mns=explode("_",$methodName);
            if (count($__mns)==3 && $__mns[1]=='event') {
                $eventClass=$__mns[0];
                $eventName=$__mns[2];
                if (!isset($this->events[$eventClass]))
                    $this->events[$eventClass]=array();
                $this->events[$eventClass][$eventName]=array($this,$methodName);
            }
        }
    }

    public function triggerEvent() {
        $parameters=func_get_args();
        $eventName=array_shift($parameters);
        if (count($parameters)==1)
            $retVal=$parameters[0];
        else if(count($parameters)>1)
            $retVal=$parameters;
        else
            $retVal=null;
        foreach($this->events as $eventClass =>$events) {
            if (isset($this->events[$eventClass][$eventName])) {
                $retVal=call_user_func_array($this->events[$eventClass][$eventName],$parameters);
            }
        }
        return $retVal;
    }

    public function eventsList() {
        return $this->events;
    }

    public function unbindEvent($eventClass,$event) {
        $static = !(isset($this) && get_class($this) == __CLASS__);
        if ($static)
            $instance=new static;
        else
            $instance=$this;

        if (!$eventClass) {
            foreach($instance->events as $class => $events) {
                if (isset($events[$event]))
                    unset($instance->events[$class][$event]);
            }
        } else if (isset($instance->events[$eventClass]))
                unset($instance->events[$eventClass][$event]);

//        dump_var($instance->events);
        return $instance;
    }
}
