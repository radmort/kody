# cita napätie z analogoveho vstupu a pracuje snim presnejsie
from machine import ADC, Pin

adc = ADC(Pin(36))
adc.atten(ADC.ATTN_11DB)

val2 = adc.read()
print(val2)
val1 = adc.read_uv()
print(val1)

FS = 3.3
LSB = FS/4095
Hodnota = LSB*val2
print(Hodnota)