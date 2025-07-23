from machine import ADC, Pin

adc = ADC(Pin(36))
adc.atten(ADC.ATTN_11DB)

val = adc.read()
print(val *(3.3/4095))
val = adc.read_uv()
print(val/pow(10,6))
