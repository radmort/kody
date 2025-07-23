# uprava jasu ledky pomocou dvoch tlacidiel s pomocnou led signalizujuca stav urovne svietenia povodnej led
from machine import Pin, PWM
import time

tlacidlo1 = Pin(32,Pin.IN)
tlacidlo2 = Pin(33,Pin.IN)
led_hlavna = PWM(Pin(12))
led_pomocna = Pin(14,Pin.OUT)

jas = 0 # inicializacia povodneho percenta stavu duty
krok = 50 #krok o kolko zvacsujeme percento casu kedy je zapnuty
hranica_trvale = 256 #25% svieti
hranica_blikania = 512 #50% blika

while True:
    
    if tlacidlo1.value() != 1: # ak tlac1 je zopnute prejde do dalsieho ifu
        if jas < 1023:  # odchitenie vinimky ziarovka svieti na max v pociatocnom stave
            jas += krok # logika zvacsenia jasu
            if jas <= 1023: # kontrola ci je jas v povolenom rozmedzi
                if jas+krok >= 1023:  # logika aby dosiahol maximalnu hodnotu jasu pri roznych krokoch
                    jas = 1023
                led_hlavna.duty(jas)  # nastavuje aktualnu hodnotu jasu pomocou .duty z PWM, teda upravuje pomer kedy je stav 1
                print("Aktualny jas(duty cycle):", jas) # vypis aktualnej hodnoty percenta casu kedy je stav v 1
                time.sleep_ms(500)  # caka pre prijemnejsie zobrazenie
                
    if tlacidlo2.value() != 1: # ak tlac2 je zopnute prejde do dalsieho ifu
        if jas > 0: # odchitenie vinimky ziarovka nesvieti na pociatocnom stave
            jas -= krok # logika zmensenia jasu
            if jas >= 0: # kontrola ci je jas v povolenom rozmedzi 
                if jas-krok <= 0:  # logika aby dosiahol minimalnu hodnotu jasu pri roznych krokoch
                    jas = 0
                led_hlavna.duty(jas)  # nastavuje aktualnu hodnotu jasu pomocou .duty z PWM, teda upravuje pomer kedy je stav 1
                print("Aktualny jas(duty cycle):", jas)  # vypis aktualnej hodnoty percenta casu kedy je stav v 1
                time.sleep_ms(500) # caka pre prijemnejsie zobrazenie
    
    if jas <= hranica_trvale and jas != 0:   # ak je percento jasu na hl. ledke mensi ako 25% svieti
        led_pomocna.value(1) # zap pomocnu led
    elif jas< hranica_blikania and jas != 0: # ak je percento jasu na hl. ledke mensi ako 50% blika
        led_pomocna.value(1)  # zap pomocnu led
        time.sleep_ms(200)   # caka nez prejde cas pre blikanie 
        led_pomocna.value(0)  # vyp pomocnu led
        time.sleep_ms(200)  # caka nez prejde cas pre blikanie 
    else:
        led_pomocna.value(0)  # vyp pomocnu led