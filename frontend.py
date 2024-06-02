from sense_emu import SenseHat
import time
import requests
class IotDevice:
    def __init__(self):
        self.sense = SenseHat()
        self.current_date = {'day': 1, 'month': 1, 'year': 2022}
        self.current_mode = 'normal'  # 'normal', 'setup', 'date_entry', 'day', 'month'
        self.site_number = 1
        self.prediction = None
        self.display_mode = 'temperature'  # 'temperature', 'humidity'

    def send_request(self, date, site):
        web_server = 'http://localhost/server.php'  # Replace with your server URL
        payload = {
            'day': date['day'],
            'month': date['month'],
            'year': date['year'],
            'site': site
        }
        try:
            print("Sending request to server...")
            response = requests.get(web_server, params=payload)  # Use GET with query parameters
            print("Request sent.")
            if response.status_code == 200:
                print("Success!")
                print(response.text)
                return response.json()
            else:
                print(f"Error: {response.status_code}")
                return None
        except requests.exceptions.RequestException as e:
            print(f"Request Exception: {e}")
            return None

    def display_text(self, text):
        self.sense.show_message(text, scroll_speed=0.05)

    def update_display(self):
        if self.display_mode == 'temperature':
            self.display_text('Temp Mode')
        else:
            self.display_text('Hum Mode')

    def check_within_range(self, current, predicted_min, predicted_max):
        return predicted_min <= current <= predicted_max

    def set_screen_color(self, is_within_range):
        color = (0, 255, 0) if is_within_range else (255, 0, 0)
        self.sense.clear(color)

    def handle_joystick(self, event):
        if event.action != 'pressed':
            return

        if self.current_mode == 'normal':
            if event.direction == 'middle':
                self.current_mode = 'setup'
                self.display_text(f"Site: {self.site_number}")
            elif event.direction == 'left' or event.direction == 'right':
                self.display_mode = 'temperature' if self.display_mode == 'humidity' else 'humidity'
                self.update_display()
        elif self.current_mode == 'setup':
            if event.direction == 'up':
                self.site_number = min(self.site_number + 1, 5)
                self.display_text(f"Site: {self.site_number}")
            elif event.direction == 'down':
                self.site_number = max(self.site_number - 1, 1)
                self.display_text(f"Site: {self.site_number}")
            elif event.direction == 'middle':
                self.current_mode = 'date_entry'
                self.display_text('Set Date')
        elif self.current_mode == 'date_entry':
            if event.direction == 'left':
                self.current_mode = 'day'
                self.display_text(f"Day: {self.current_date['day']}")
            elif event.direction == 'right':
                self.current_mode = 'month'
                self.display_text(f"Month: {self.current_date['month']}")
            elif event.direction == 'middle':
                self.current_mode = 'normal'
                self.prediction = self.send_request(self.current_date, self.site_number)
                if self.prediction:
                    print(f"Location: {self.prediction['location_name']}")
                    print(f"Predicted Min Temp: {self.prediction['min_temp']} °C")
                    print(f"Predicted Max Temp: {self.prediction['max_temp']} °C")
                    print(f"Predicted Min Humidity: {self.prediction['min_humidity']} %")
                    print(f"Predicted Max Humidity: {self.prediction['max_humidity']} %")
                self.display_text('Prediction Set')
        elif self.current_mode == 'day':
            if event.direction == 'up':
                self.current_date['day'] = min(self.current_date['day'] + 1, 31)
                self.display_text(f"Day: {self.current_date['day']}")
            elif event.direction == 'down':
                self.current_date['day'] = max(self.current_date['day'] - 1, 1)
                self.display_text(f"Day: {self.current_date['day']}")
            elif event.direction == 'left' or event.direction == 'right':
                self.current_mode = 'month'
                self.display_text(f"Month: {self.current_date['month']}")
            elif event.direction == 'middle':
                self.current_mode = 'normal'
                self.prediction = self.send_request(self.current_date, self.site_number)
                if self.prediction:
                    print(f"Location: {self.prediction['location_name']}")
                    print(f"Predicted Min Temp: {self.prediction['min_temp']} °C")
                    print(f"Predicted Max Temp: {self.prediction['max_temp']} °C")
                    print(f"Predicted Min Humidity: {self.prediction['min_humidity']} %")
                    print(f"Predicted Max Humidity: {self.prediction['max_humidity']} %")
                self.display_text('Prediction Set')
        elif self.current_mode == 'month':
            if event.direction == 'up':
                self.current_date['month'] = min(self.current_date['month'] + 1, 12)
                self.display_text(f"Month: {self.current_date['month']}")
            elif event.direction == 'down':
                self.current_date['month'] = max(self.current_date['month'] - 1, 1)
                self.display_text(f"Month: {self.current_date['month']}")
            elif event.direction == 'left' or event.direction == 'right':
                self.current_mode = 'day'
                self.display_text(f"Day: {self.current_date['day']}")
            elif event.direction == 'middle':
                self.current_mode = 'normal'
                self.prediction = self.send_request(self.current_date, self.site_number)
                if self.prediction:
                    print(f"Location: {self.prediction['location_name']}")
                    print(f"Predicted Min Temp: {self.prediction['min_temp']} °C")
                    print(f"Predicted Max Temp: {self.prediction['max_temp']} °C")
                    print(f"Predicted Min Humidity: {self.prediction['min_humidity']} %")
                    print(f"Predicted Max Humidity: {self.prediction['max_humidity']} %")
                self.display_text('Prediction Set')

    def run(self):
        self.sense.stick.direction_any = self.handle_joystick
        self.display_text('Nor Mo')

        while True:
            if self.current_mode == 'normal' and self.prediction:
                current_temp = self.sense.get_temperature()
                current_humidity = self.sense.get_humidity()

                if self.display_mode == 'temperature':
                    is_within_range = self.check_within_range(current_temp, self.prediction['min_temp'], self.prediction['max_temp'])
                else:
                    is_within_range = self.check_within_range(current_humidity, self.prediction['min_humidity'], self.prediction['max_humidity'])

                self.set_screen_color(is_within_range)

            time.sleep(1)

if __name__ == "__main__":
    device = IotDevice()
    device.run()

