from machine import Pin, PWM
import time

pwm0 = PWM(Pin(32))
pwm0.freq(10000)

while True:
    for i in range(1,1023):
        pwm0.duty(i)
        time sleep_ms(2)
    for i in range(1023,1,-1):
        pwm0.duty(i)
        time sleep_ms(2)