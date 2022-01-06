# Break-the-pieces
My PHP Solution for this problem: https://www.codewars.com/kata/527fde8d24b9309d9b000c4e/php

## Example
```
$shape = implode("\n", ["        +--+                 ",
                        "        |  |       +-----+   ",
                        "        |  |       |     |   ",
                        "    +---+--+-------+--+--+   ",
                        "    |   |  |          |      ",
                        " +--+   +--+    +-+   |      ",
                        " |  |           | |   |      ",
                        " |  |           | |   |      ",
                        " |  |           | |   |      ",
                        " |  +-----------+-+-+-+-+    ",
                        " |  |           | | |   |    ",
                        " +--+           | | +---+    ",
                        "                | |          ",
                        "                +-+          ",
                        "                             "]);

$shapes = (new BreakPieces())->process($shape);
```

## Output
![Schermata 2022-01-06 alle 17 19 07](https://user-images.githubusercontent.com/10846876/148414559-5a78fc67-858e-4dfb-a4d1-7232c67d7ce0.png)
