#!/usr/bin/python
# codig=utf-8

import spidev
import time

spi = spidev.SpiDev()
spi.open(0,0)
response = spi.xfer([1,128,0])
# These two values depending on the used sensor
maximum = 480
minimum = 1024

if 0 <= response[1] <= 3:
    current = ((response[1] * 256) + response[2])
    percent = ((current - minimum) * 100) / (maximum -minimum)
    print percent, "%"
