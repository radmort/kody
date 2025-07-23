from qiskit import QuantumCircuit                         
from qiskit.quantum_info import Statevector               
from qiskit.primitives import StatevectorSampler
from qiskit.visualization import plot_histogram       

grover_circ = QuantumCircuit(4)                          
iterator = 3 # success probability 

# Reset all qubits to |0> state to ensure clean initial state
for qubit in range(4):     
    grover_circ.reset(qubit)  

#Applications of hadamar's gates
grover_circ.h([0,1,2,3])
for _ in range(iterator): # success probability to perform better for the part of the circuit by multiple iteration
    # Flip control qubits to transform |0100> to |111> so we can detect it with mcx = multi-controlled X gate
    grover_circ.x(0)  
    grover_circ.x(1)  
    grover_circ.x(3)  
    grover_circ.h(2)
    # Apply the oracle: multi-controlled X with controls [q3,q2,q0] acting on q1 (target)
    grover_circ.mcx([3,1,0], 2)
    grover_circ.h(2)
    # Uncompute the flips (restore original basis)
    grover_circ.x(0)
    grover_circ.x(1)
    grover_circ.x(3)


    # DIFFUSER (inversion about the mean)
    # Step 1: Apply Hadamard to all qubits to go to uniform superposition
    grover_circ.h([0,1,2,3])

    # Step 2: Apply X to all but qubit 1 
    grover_circ.x([0,1,2,3])
    # Step 3: Apply multi-controlled-Z via mcx as controlled-X around middle qubit
    grover_circ.h(2)
    grover_circ.mcx([0,1,3], 2)
    grover_circ.h(2)
    # Step 4: Undo the X gates
    grover_circ.x([0,1,2,3])

    # Step 5: Apply Hadamard again to return to computational basis
    grover_circ.h([0,1,2,3])

# Get the full quantum statevector (before measurement)
stateV = Statevector(grover_circ) 

# Add measurement to all qubits
grover_circ.measure_all() 

# Run the circuit using statevector-based sampler
sampler = StatevectorSampler() 
shots = 1000
job = sampler.run([grover_circ], shots=shots)

result = job.result()[0]  # Get the result of sampling


state = result.data.meas.get_bitstrings()  # Extract bitstrings from measurement 


print("State of quantum circuit:", stateV) # Print the final quantum statevector 


print(state) # Print all measured bitstrings from the sampler


counts = result.data.meas.get_counts() # Get measurement counts (frequency of each bitstring)


prob_dict = {mate: c / shots for mate, c in counts.items()} # Convert counts to probability dictionary
print(prob_dict)


print(f"'0100': {counts.get('0100', 0)} from {shots} shots") # Highlight the count for the target state '0010' in qiskit the left most qbit is n-1 and 0 is the rightmost qbit so correct notation is '0100' for qiskit

grover_circ.draw("mpl") # Draw the quantum circuit using matplotlib (works in Jupyter Notebooks)
plot_histogram({mate: c / shots for mate, c in counts.items()})

#print(grover_circ.draw()) # Draw ASCII version of the quantum circuit (for .py or terminal use)
