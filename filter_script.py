import csv

allowed_stands = ['A0', 'A1', 'A2', 'A3', 'B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'B11', 'B12', 'B13']
input_file = 'DATASET AMC .csv'
output_file = 'DATASET AMC filtered.csv'

with open(input_file, 'r', newline='') as infile, open(output_file, 'w', newline='') as outfile:
    reader = csv.reader(infile)
    writer = csv.writer(outfile)

    header = next(reader)
    writer.writerow(header)

    # Find the index of the 'PARKING STAND' column
    try:
        stand_index = header.index('PARKING STAND')
    except ValueError:
        print("Column 'PARKING STAND' not found in the CSV file.")
        exit()

    for row in reader:
        if len(row) > stand_index and row[stand_index] in allowed_stands:
            writer.writerow(row)

print(f"Filtered data saved to {output_file}")