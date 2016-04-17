<?php

namespace Dodecagon;

class Disk {

    /**
     * Diameters in relation to the canvas size
     */
    const DIAMETER_PRIMARY_OUTER = 0.95;
    const DIAMETER_PRMIARY_INNER = 0.71;
    const DIAMETER_SECONDARY_OUTER = 0.68;
    const DIAMETER_SECONDARY_INNER = 0.63;

    /**
     * Margin between parts
     */
    const MARGIN = 0.05;

    /**
     * Rings each representing a temperature
     */
    const RING_PRIMARY = 'ring_primary';
    const RING_SECONDARY = 'ring_secondary';

    /**
     * Edge of ring
     */
    const OUTER = 'outer';
    const INNER = 'inner';


    /**
     * Temperatures
     */
    private $temperature; // ˚C
    private $secondaryTemperature; // ˚C

    /**
     * Width and heigh of canvas in px
     */
    private $canvas;

    public function __construct($canvas, $temperature) {

        if ($temperature < -11 || $temperature > 36) {
            throw new \Exception("Temperature out of bounds [-11;36]", 1);
        }

        $canvas = intval($canvas);

        if ($canvas < 10) {
            throw new \Exception("Canvas size is too small", 1);
        }

        $this->temperature = $temperature;
        $this->secondaryTemperature = NULL;
        $this->canvas = $canvas;
    }

    /**
     * Disk radius
     *
     * @return float Inner or outer radius of disk in px
     */
    private function getRadius ($innerOrOuter, $ring)
    {
        if ($ring == SELF::RING_PRIMARY) {
            return $this->canvas * ($innerOrOuter == self::OUTER ? self::DIAMETER_PRIMARY_OUTER : self::DIAMETER_PRMIARY_INNER) / 2;
        }

        return $this->canvas * ($innerOrOuter == self::OUTER ? self::DIAMETER_SECONDARY_OUTER : self::DIAMETER_SECONDARY_INNER) / 2;
    }

    /**
     * The x of y coordinate of the center point of the disk
     * 
     * @return float 
     */
    private function getCenter ()
    {
        return $this->canvas / 2;
    }

    private function getPart($position, $color, $ring) {
        if ($position < 0) {
            throw new \Exception("Position smaller than zero", 1);
        }
        if ($position > 11) {
            throw new \Exception("Position greater than eleven", 1);
        }
        $points =[
            $this->getPoint($position, self::OUTER, TRUE, $ring),
            $this->getPoint($position + 1, self::OUTER, FALSE, $ring),
            $this->getPoint($position + 1, self::INNER, FALSE, $ring),
            $this->getPoint($position, self::INNER, TRUE, $ring),
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
    private function getPoint($position, $innerOrOuter, $beginning, $ring) {

        if (!in_array($innerOrOuter, array(self::INNER, self::OUTER))) {
            throw new \Exception("Wrong circle: $innerOrOuter", 1);
        }
        if (!is_bool($beginning)) {
            throw new \Exception("Param \$beginning not boolean", 1);
            
        }
        $sin = sin(($position + ($beginning ? self::MARGIN : -self::MARGIN)) * pi() / 6);
        $cos = cos(($position + ($beginning ? self::MARGIN : -self::MARGIN)) * pi() / 6);

        $x = $sin * $this->getRadius($innerOrOuter, $ring) + $this->getCenter();
        $y = $this->canvas - $cos * $this->getRadius($innerOrOuter, $ring) - $this->getCenter();

        return array('x' => $x, 'y' => $y);
    }

    public function getParts($ring) {
        
        $temperature = ($ring == self::RING_PRIMARY) ? $this->temperature : $this->secondaryTemperature;
        
        $parts = array();
        for($i = 0; $i < 12; $i++) {

            $color = $this->getDefaultColor();
            
            if ($temperature > 0) {
                if (($temperature - 1) % 12 >= $i) {
                    $color = $this->getRangeColor($ring);
                }
            } else {
                if ($i >= $temperature + 12) {
                    $color = $this->getRangeColor($ring);    
                }
            }
            $parts[] = $this->getPart($i, $color, $ring);
        }
        return $parts;
    }

    private function getRangeColor ($ring)
    {
        //https://coolors.co/app/fa7921-fe9920-c00000-566e3d-0c4767
        $colors = [
            '#0c4767', // blue
            '#566e3d', // green
            '#fa7921', // orange
            '#c00000', // red
        ];
        $temperature = ($ring == self::RING_PRIMARY) ? $this->temperature : $this->secondaryTemperature;
        $range = intval(($temperature - 1) / 12 + 1);
        $range = min(max($range, 0), 3);

        if (!isset($colors[$range])) {
            throw new \Exception("No color for range $range found", 1);
            
        }
        return $colors[$range];
        
    }

    private function getDefaultColor()
    {
        return '#eeeeee';
    }

    /**
     * @return string Get the dodecagon SVG 
     */
    public function getSvg()
    {
     
        $svg = '<svg width="' . $this->canvas . '" height="' . $this->canvas . '">' . PHP_EOL;
        $svg .= '<text text-anchor="middle" x="' . $this->getXTextCoord() . '" y="' . $this->getYTextCoord() . '" style="font-size: ' . $this->getFontSize() . 'px;font-family:sans-serif;" fill="' . $this->getRangeColor(self::RING_PRIMARY) . '">' . $this->temperature . ' °C</text>';

        foreach($this->getParts(self::RING_PRIMARY) as $part) {
            $svg .= '<polygon points="' . $part['pointsString'] . '" style="fill:' . $part['color'] . ';" />' . PHP_EOL;
        }
        if (!is_null($this->secondaryTemperature)) {
            foreach($this->getParts(self::RING_SECONDARY) as $part) {
                $svg .= '<polygon points="' . $part['pointsString'] . '" style="fill:' . $part['color'] . ';" />' . PHP_EOL;
            }
        }
        $svg .= '</svg>' . PHP_EOL;
  
        return $svg;
    }

    private function getFontSize()
    {
        return intval(0.289556962 * $this->canvas - 33.5 + 0.5);

    }

    private function getYTextCoord()
    {
        return intval(0.6107594937 * $this->canvas - 16 + 0.5);

    }

    private function getXTextCoord()
    {
        return $this->canvas / 2;
    }

    public function setSecondaryTemperature ($temperature)
    {
        if ($temperature < -11 || $temperature > 36) {
            throw new \Exception("Secondary temperature out of bounds [-11;36]", 1);
        }

        $this->secondaryTemperature = $temperature;
    }
}
?>
