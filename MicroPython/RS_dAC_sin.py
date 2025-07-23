from machine import Pin, DAC
import time, math
dacPin1 = Pin(25)
dac1 = DAC(dacPin1)
while True:
    for deg in range(0,360):
        pro_vystup = 128+80*math.sin(deg*math.pi/180)
        vystup = int(pro_vystup)
        dac1.write(vystup)
        time.sleep_us(50)