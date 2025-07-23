# program vypisuje teplotu zo senzora ds18b20 na oled display
from machine import Pin
import onewire, ds18x20
import time
from machine import Pin, SoftI2C
from ssd1306 import SSD1306_I2C

i2c=SoftI2C(scl=Pin(22),sda=Pin(21))

oled_width=128
oled_height=64
oled= SSD1306_I2C(oled_width, oled_height, i2c)
while True:
    ow= onewire.OneWire(Pin(16))
    ds= ds18x20.DS18X20(ow)
    roms= ds.scan()
    ds.convert_temp()
    time.sleep_ms(750)
    for rom in roms:
        oled.text(str(ds.read_temp(rom)),0,0)
        oled.show()
        oled.fill(0)