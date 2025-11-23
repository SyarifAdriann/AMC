
import csv

def categorize_airline(airline):
    commercial_airlines = ['PELITA', 'BATIK AIR', 'GARUDA', 'SUSI AIR', 'CITILINK', 'FLY JAYA']
    cargo_airlines = ['B.B.N', 'TRIGANA', 'JAYAWIJAYA', 'AIRNESIA', 'CITILINK CARGO', 'TRI MG']
    military_airlines = ['TNI AU']

    if airline in commercial_airlines:
        return 'Commercial'
    elif airline in cargo_airlines:
        return 'Cargo'
    elif airline in military_airlines:
        return 'Military'
    else:
        return 'Charter'

input_file = 'C:\\xampp\\htdocs\\amc\\DATASET AMC 2.csv'
output_file = 'C:\\xampp\\htdocs\\amc\\DATASET AMC 2_categorized.csv'

# Read all data into memory to handle potential empty rows
rows = []
with open(input_file, 'r') as infile:
    reader = csv.reader(infile)
    try:
        header = next(reader)
        rows.append(header + ['CATEGORY'])
        for row in reader:
            rows.append(row)
    except StopIteration:
        # Handle empty file
        pass

with open(output_file, 'w', newline='') as outfile:
    writer = csv.writer(outfile)
    if not rows:
        writer.writerow(['REGISTRATION','TYPE','ON BLOCK','OFF BLOCK','PARKING STAND','FROM','TO','ARR','DEP','OPERATOR / AIRLINES','CATEGORY'])
    else:
        writer.writerow(rows[0]) # write header
        for row in rows[1:]:
            if len(row) > 9:
                airline = row[9].strip().upper()
                category = categorize_airline(airline)
                writer.writerow(row + [category])
            else:
                # Also add the empty rows to the new file
                writer.writerow(row + ['Charter'])
