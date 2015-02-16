<?php

namespace Dahl\Input\Keys;

class Xterm extends Default$
{
     const   HOME        = "\x1bOH",
             END         = "\x1bOF",
             BACKSPACE   = "\x7f",
             F1          = "\x1bOP",
             F2          = "\x1bOQ",
             F3          = "\x1bOR",
             F4          = "\x1bOS";
}
