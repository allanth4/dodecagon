<?php

namespace Dedecagon;

class Disk {

    /**
     * Width and heigh of canvas in px
     * @todo Move to constructor
     */
    const CANVAS = 400;
    
    /**
     * The outer diameter in relation to the canvas size
     */
    const OUTER_DIAMETER = 0.95;
    
    /**
     * The inner diameter in relation to the canvas size
     */
    const INNER_DIAMETER = 0.71;

    /**
     * Margin between parts
     */
    const MARGIN = 1/20;

    const OUTER = 'outer';
    const INNER = 'inner';

    /**
     * The inner diameter in relation to the canvas size
     */
    private $temperature; // ˚C

    public function __construct($temperature) {

        if ($temperature < -11 || $temperature > 36) {
            throw new Exception("Temperature out of bounds [-11;36]", 1);
            
        }
        $this->temperature = $temperature;
    }

    /**
     * Disk radius
     *
     * @return float Inner or outer radius of disk in px
     */
    private function getRadius ($innerOrOuter)
    {
        return self::CANVAS * ($innerOrOuter == self::OUTER ? self::OUTER_DIAMETER : self::INNER_DIAMETER) / 2;
    }

    /**
     * The x of y coordinate of the center point of the disk
     * 
     * @return float 
     */
    private function getCenter ()
    {
        return self::CANVAS / 2;
    }

    private function getPart($position, $color) {
        if ($position < 0) {
            throw new \Exception("Position smaller than zero", 1);
        }
        if ($position > 11) {
            throw new \Exception("Position greater than eleven", 1);
        }
        $points =[
            $this->getPoint($position, self::OUTER, TRUE),
            $this->getPoint($position + 1, self::OUTER, FALSE),
            $this->getPoint($position + 1, self::INNER, FALSE),
            $this->getPoint($position, self::INNER, TRUE),
        ];

        $pointsString = '';
        foreach($points as $point) {
            $pointsString .= $point['x'] . ',' . $point['y'] . ' ';
        }


        return [
            'points' => $points,
            'pointsString' => trim($pointsString),
            'color' => $color,
        ];
    }

    /**
     * Get the coordinates of a point
     *
     * @param $position int 0-11
     * @param $innerOrOuter 
     * @param $beginning bool 
     *
     * @return array with x and y coordinates
     */
    private function getPoint($position, $innerOrOuter, $beginning) {

        if (!in_array($innerOrOuter, array(self::INNER, self::OUTER))) {
            throw new \Exception("Wrong circle: $innerOrOuter", 1);
        }
        if (!is_bool($beginning)) {
            throw new \Exception("Param \$beginning not boolean", 1);
            
        }
        $sin = sin(($position + ($beginning ? self::MARGIN : -self::MARGIN)) * pi() / 6);
        $cos = cos(($position + ($beginning ? self::MARGIN : -self::MARGIN)) * pi() / 6);

        $x = $sin * $this->getRadius($innerOrOuter) + $this->getCenter();
        $y = self::CANVAS - $cos * $this->getRadius($innerOrOuter) - $this->getCenter();

        return array('x' => $x, 'y' => $y);
    }

    public function getParts() {
        
        $parts = array();
        for($i = 0; $i < 12; $i++) {
            
            $color = $this->getDefaultColor();
            
            if ($this->temperature > 0) {
                if (($this->temperature - 1) % 12 >= $i) {
                    $color = $this->getRangeColor();
                }
            } else {
                if ($i >= $this->temperature + 12) {
                    $color = $this->getRangeColor();    
                }
            }
            $parts[] = $this->getPart($i, $color);
        }
        return $parts;
    }

    private function getRangeColor ()
    {
        //https://coolors.co/app/fa7921-fe9920-c00000-566e3d-0c4767
        $colors = [
            '#0c4767', // blue
            '#566e3d', // green
            '#fa7921', // orange
            '#c00000', // red
        ];
        $range = intval(($this->temperature - 1) / 12 + 1);
        $range = min(max($range, 0), 3);

        if (!isset($colors[$range])) {
            throw new Exception("No color for range $range found", 1);
            
        }
        return $colors[$range];
        
    }

    private function getDefaultColor()
    {
        return '#eeeeee';
    }
}
?>