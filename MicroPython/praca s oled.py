import time 
from machine import Pin, SoftI2C
from ssd1306 import SSD1306_I2C

i2c = SoftI2C(scl=Pin(22),sda=Pin(21))

oled_width = 128
oled_height = 64
oled = SSD1306_I2C(oled_width,oled_height,i2c)
while True:
    oled.fill(0)
    oled.show()
    time.sleep(1)
    oled.text('Hellow World 1!', 0, 0)
    oled.text('Hellow World 2!', 0, 10)
    oled.text('Hellow World 3!', 0, 20)
    oled.text('Hellow World 4!', 0, 30)
    oled.text('Hellow World 5!', 0, 40)
    oled.show()
    time.sleep(1)