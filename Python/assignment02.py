# Assignment 02:
#   For the following list of integers, write a Python script that calculates the average of the integers that are
#   divisible by 2: 1, 6, 10, 15, 99, 45, 56, 32, 150, 151, 672, 558, 789, 335, 23, 65, 47, 33

def avg_two(input: list[int]) -> float:   # Function that will calculate the average of numbers divisible by 2, with each function call, the variables are reset to 0:
    sum_of_avg: float = 0                 # Variable that will store the sum of numbers divisible by 2, 
    count: int = 0                        # Variable thats  count how many numbers are divisible by 2 
    for num in input:                     # For-loop through each number in the  list
        if (num % 2)==0:                  # Check if the number is divisible by 2, by modulo operator, if yes:
            count += 1                    # Increase count by 1
            sum_of_avg += num             # Add the number to the sum
    return sum_of_avg/count               # Return the average


input: list[int] = [1, 6, 10, 15, 99, 45, 56, 32, 150, 151, 672, 558, 789, 335, 23, 65, 47, 33] # list of integers called: input
output: float = avg_two(input)                                                                  # Call the function and store the result in 'output'
print(output)                                                                                   # Print the result as a float, with decimal part
print(int(output))                                                                              # Print the result as a whole number for Edyoucated