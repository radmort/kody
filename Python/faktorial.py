def faktorial(n: int) -> int:
    if n == 1:
        return 1
    else:
        return n* faktorial(n-1)

result: int = faktorial(5)
print(result)

