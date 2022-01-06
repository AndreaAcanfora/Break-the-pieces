<?php

class BreakPieces {

    private $shape;
    private $direction;
    private $shapeInProgress = [];
    private $shapesCompleted = [];
    private $copy = false;

    public function process($shape) {
        $this->shape = explode(PHP_EOL, $shape);

        $this->iterator(function($i, $j, $char, $actions) {
            if (count($actions) === 1 && $actions[0] === 'top_left' && !$this->copy) {
                $this->copy = true;
                $this->new();
                $this->copyAndRemove($i, $j);
            }
        });
        $this->removeSpaces();
        $this->checkShapes();
        return $this->shapesCompleted;
    }

    public function copyAndRemoveAction($i, $j) {
        $this->shapeInProgress[$i][$j] = $this->shape[$i][$j];
        $this->shape[$i][$j] = ' ';
    }

    public function copyAndRemoveNextAction($i, $j, $direction) {
        $ii = $i;
        $jj = $j;
        switch($direction) {
            case 'right': $jj++; break;
            case 'left': $jj--; break;
            case 'down': $ii++; break;
            case 'up': $ii--; break;
        }
        $nextAction = $this->getActions($this->shape[$ii][$jj], $ii, $jj);
        $this->copyAndRemoveAction($i, $j);
        if ($this->completeShape($nextAction[0] ?? NULL)) return true;
        $this->direction = $direction;
        $this->copyAndRemove($ii, $jj, $nextAction);
    }

    public function copyAndRemove($i, $j, $actions = NULL) {
        $char = $this->shape[$i][$j] ?? NULL;
        if (!$char) return 0;

        $actions = $actions ?? $this->getActions($char, $i, $j);

        if (count($actions) > 1) $this->copy = false;

        if (!$this->copy) return $this->copy($i, $j, $actions, true);

        if (!$actions && $char === '+') {
            $char = '-';
            $this->shape[$i][$j] = $char;
            $actions = $this->getActions($char, $i, $j);
        }

        $this->copyAndRemoveNextAction($i, $j, $this->getDirection($actions[0]));
    }

    public function completeShape($nextAction) {
        if ($nextAction === 'space' && $this->direction === 'up') {
            $this->shapesCompleted[] = $this->shapeInProgress;
            $this->shapeInProgress = [];
            $this->copy = false;
            return true;
        }
        return false;
    }

    public function getDirection($action) {
        switch($action) {
            case 'top_left':
                return $this->direction == 'left' ? 'down' : 'right';
            case 'top_right':
                return 'down';
            case 'bottom_right':
                return $this->direction == 'right' ? 'up' : 'left';
            case 'bottom_left':
                return $this->direction == 'down' ? 'right' : 'up';
        }
        return $this->direction;
    }

    public function iterator($callback, $shape = NULL) {
        $shape = $shape ?? $this->shape;
        $rows  = count($shape);
        $cols  = strlen(current($shape));

        for ($i=0; $i < $rows; $i++) { 
            $row = $shape[$i];
            for ($j=0; $j < $cols; $j++) { 
                $char = $shape[$i][$j] ?? NULL;
                if ($char) {
                    $actions = $this->getActions($char, $i, $j);
                    $callback($i, $j, $char, $actions);
                }
            }
        }
    }

    public function goDirection($direction, $i, $j, $nextI, $nextJ, $copy, $piece) {
        $this->direction = $direction;
        $nextAction = $this->getActions($this->shape[$nextI][$nextJ], $nextI, $nextJ);
        $this->shapeInProgress[$i][$j] = $piece ? $piece : $this->shape[$i][$j];
        if ($copy) {
            $this->copy = true;
            $this->copyAndRemove($nextI, $nextJ, $nextAction);
        } else {
            $this->copy($nextI, $nextJ, $nextAction);
        }
    }

    public function up($i, $j, $copy = false, $piece = NULL) {
        $this->goDirection('up', $i, $j, $i-1, $j, $copy, $piece);
    }

    public function down($i, $j, $copy = false, $piece = NULL) {
        $this->goDirection('down', $i, $j, $i+1, $j, $copy, $piece);
    }

    public function left($i, $j, $copy = false, $piece = NULL) {
        $this->goDirection('left', $i, $j, $i, $j-1, $copy, $piece);
    }

    public function right($i, $j, $copy = false, $piece = NULL) {
        $this->goDirection('right', $i, $j, $i, $j+1, $copy, $piece);
    }

    public function hasCubeBottomLeft($i, $j) {
        $cubes = 0;
        for ($ii=$i+1, $jj = $j-1; isset($this->shape[$ii][$jj]); $ii++) {
            $cubes += (in_array($this->shape[$ii][$jj], ['+', '-']));
        }
        return $cubes;
    }

    public function hasAngleOnTop($i, $j) {
        $angles = 0;
        for ($ii=$i-1; isset($this->shape[$ii][$j]); $ii--) {
            $angles += ($this->shape[$ii][$j] === '+');
        }
        return $angles;
    }

    public function oneAngle($i, $j, $char, $actions) {
        switch($actions[0]) {
            case 'top_right':
            case 'bottom_right':
                $this->left($i, $j);
                break;
            case 'top_left':
                $this->down($i, $j);
                break;
            case 'bottom_left':
                $this->right($i, $j);
                break;
        }
    }

    public function doubleAngle($i, $j, $char, $actions, $fromCopyAndRemove) {

        if (!array_diff($actions, ['bottom_right', 'top_right'])) {
            switch($this->direction) {
                case 'right':
                    return $this->down($i, $j, true);
                case 'up':
                    return $this->up($i, $j, !$fromCopyAndRemove, '|');
                default:
                    $this->left($i, $j, !$this->hasCubeBottomLeft($i, $j));
            }
        } else if (!array_diff($actions, ['bottom_right', 'bottom_left'])) {
            $this->left($i, $j, !$fromCopyAndRemove);
        } else if (!array_diff($actions, ['top_left', 'top_right'])) {
            switch($this->direction) {
                case 'left':
                    return $this->left($i, $j, !$this->hasCubeBottomLeft($i, $j), '-');
                case 'up':
                    return $this->right($i, $j, true);
                default:
                    $this->down($i, $j);
            }
        } else if (!array_diff($actions, ['bottom_left', 'top_left'])) {
            switch($this->direction) {
                case 'down':
                    return $this->down($i, $j, !$fromCopyAndRemove, '|');
                case 'up':
                    return $this->right($i, $j);
                case 'left':
                    $this->up($i, $j);
            }
        }
    }

    public function multipleAngles($i, $j, $char, $actions) {
        switch($this->direction) {
            case 'up':
                return $this->right($i, $j);
            case 'right':
                return $this->down($i, $j, true);
        }
        $this->left($i, $j, !$this->hasCubeBottomLeft($i, $j));    
    }

    public function goToUp($i, $j, $actions) {
        return ($this->shape[$i-1][$j] ?? NULL) === '|' && ($this->direction === 'left' || (
            $this->direction === 'right' && count($actions) === 1 && $actions[0] === 'bottom_right'
        ));
    }

    public function copy($i, $j, $actions = NULL, $fromCopyAndRemove = false) {
        $char = $this->shape[$i][$j] ?? NULL;
        if ($char) {
            $actions = $actions ?? $this->getActions($char, $i, $j);

            if ($char !== '+') return $this->{$this->direction}($i, $j);

            if ($this->goToUp($i, $j, $actions)) return $this->up($i, $j, !$this->hasAngleOnTop($i, $j));

            switch(count($actions)) {
                case 1:
                    return $this->oneAngle($i, $j, $char, $actions);
                case 2:
                    return $this->doubleAngle($i, $j, $char, $actions, $fromCopyAndRemove);
                case 4:
                    $this->multipleAngles($i, $j, $char, $actions);
            }
        }
    }

    public function new() {
        $new = $this->shape;
        foreach($new as $kk => $vv) $new[$kk] = str_replace(['+','|','-'], ' ', $new[$kk]);
        $this->shapeInProgress = $new;
    }

    public function removeSpaces() {
        foreach ($this->shapesCompleted as $k => $shape) {
            foreach ($shape as $kk => $vv) $shape[$kk] = str_split($shape[$kk]);
                
            $plain = trim(implode('', array_column($shape, '0')));

            while(!$plain) {
                foreach ($shape as $kk => $vv) $shape[$kk] = array_slice($shape[$kk], 1);
                $plain = trim(implode('', array_column($shape, '0')));
            }

            $height = count($shape);
            for ($i=0; $i < $height;) { 
                if (empty($shape[$i])) {
                    $i++;
                    continue;
                }
                $counts = array_count_values($shape[$i]);
                if (count($counts) === 1) {
                    unset($shape[$i]);
                } else {
                    $i++;
                }
            }
            foreach ($shape as $kk => $vv) $shape[$kk] = rtrim(implode('', $shape[$kk]));
            $this->shapesCompleted[$k] = $shape;
        }
    }

    public function checkShapes() {
        foreach ($this->shapesCompleted as $k => $shape) {
            $shape = $this->shapesCompleted[$k] = array_values($shape);
            $this->iterator(function($i, $j, $char, $actions) use($shape, $k) {
                if ($char == '-' && ($shape[$i-1][$j] ?? '') == '|' && ($shape[$i+1][$j] ?? '') == '|') {
                    $this->shapesCompleted[$k][$i][$j] = '|';
                }
            }, $shape);
            $this->shapesCompleted[$k] = implode(PHP_EOL, $this->shapesCompleted[$k]);
        }
    }

    private function getActions($char, $i, $j) {
        return [
            '+' => $this->getActionsAngle($i, $j),
            '-' => ['border'],
            '|' => ['wall'],
            ' ' => ['space'],
        ][$char];
    }

    private function getActionsAngle($i, $j) {
        $actions = [];
        foreach ([[1,1,'top_left'],[-1,1,'top_right'],[1,-1,'bottom_left'],[-1,-1,'bottom_right']] as $v) 
            (($this->shape[$i][$j+$v[0]] ?? 0) === '-' && ($this->shape[$i+$v[1]][$j] ?? 0) === '|') && ($actions[] = $v[2]);
        return $actions;
    }
}
