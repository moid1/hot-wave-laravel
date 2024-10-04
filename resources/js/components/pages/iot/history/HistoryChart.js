import React from 'react';
import ReactApexChart from 'react-apexcharts';
import "@fortawesome/fontawesome-free/css/all.css";
import Preloader from "../../../components/Preloader";
import { convertLocalTimeToUTCString } from "../DateParser";

class HistoryChart extends React.Component {
    constructor(props) {
        super(props);
        this.dataSource = props.dataSource;
        this.state = {
            loaded: false,
            series: [],
            comments: {}, // Store comments for each data point
            tolerance: {
                voltage: 10, // example tolerance for voltage
                temperature: 75, // example tolerance for temperature
                humidity: 80, // example tolerance for humidity
            },
            options: {
                chart: {
                    id: 'historyChart',
                    height: this.props.height,
                    type: 'line',
                    zoom: {
                        type: 'x',
                        enabled: true,
                    },
                    animations: {
                        enabled: true
                    },
                    dropShadow: {
                        enabled: false,
                        top: 0,
                        left: 0,
                        blur: 1,
                        opacity: 0.5
                    },
                },
                title: {
                    text: 'History Data : ' + this.props.device.sn,
                    align: 'center',
                    margin: 0,
                    offsetX: 0,
                    offsetY: 0,
                    floating: false,
                    style: {
                        fontSize: '16px',
                        fontWeight: 700,
                        fontFamily: 'sans-serif',
                        color: '#5c5c5c'
                    },
                },
                stroke: {
                    width: 2,
                    curve: 'straight',
                    lineCap: 'round',
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    x: {
                        format: "dd MMM-HH:mm"
                    },
                    y: [{
                        formatter: (v) => { return v + " V" }
                    }, {
                        formatter: (v) => { return v + " ºC" }
                    }, {
                        formatter: (v) => { return v + " %" }
                    }]
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeUTC: false
                    }
                },
                yaxis: {
                    labels: {
                        show: true,
                    },
                    title: {
                        text: "Value",
                        style: {
                            fontSize: '14px',
                            fontWeight: 700,
                        }
                    },
                },
                annotations: {
                    yaxis: [
                        {
                            y: this.props.alarms.ht,
                            borderColor: '#027D20',
                            label: {
                                borderColor: '#027D20',
                                style: {
                                    fontSize: '10px',
                                    color: '#333',
                                    background: '#00E396',
                                },
                                text: 'High ' + this.props.alarms.ht + " ºC",
                            }
                        },
                        {
                            y: this.props.alarms.lt,
                            strokeDashArray: 3,
                            borderColor: '#027D20',
                            label: {
                                borderColor: '#027D20',
                                style: {
                                    fontSize: '10px',
                                    color: '#333',
                                    background: '#00E396',
                                },
                                text: 'Low ' + this.props.alarms.lt + " ºC",
                            }
                        },
                        {
                            y: this.props.alarms.hh,
                            borderColor: '#FE9619',
                            label: {
                                borderColor: '#FE9619',
                                style: {
                                    fontSize: '10px',
                                    color: '#333',
                                    background: '#FEB019',
                                },
                                text: 'High ' + this.props.alarms.hh + " %",
                            }
                        },
                        {
                            y: this.props.alarms.lh,
                            strokeDashArray: 3,
                            borderColor: '#FE9619',
                            label: {
                                borderColor: '#FE9619',
                                style: {
                                    fontSize: '10px',
                                    color: '#333',
                                    background: '#FEB019',
                                },
                                text: 'Low ' + this.props.alarms.lh + " %",
                            }
                        },
                    ],
                },
            },
        };
    }

    render() {
        return (
            <div className='react-component-container'>
                {this.state.loaded === false ? (
                    <div className='preloader-container'>
                        <Preloader show={true} />
                    </div>
                ) : (
                    <div id="chart">
                        <ReactApexChart options={this.state.options} series={this.state.series} type="line" height={this.props.height} />
                        {this.renderComments()}
                    </div>
                )}
            </div>
        );
    }

    componentDidMount() {
        this.refreshComponent(this.props);
    }

    componentDidUpdate(prevProps) {
        if (this.props.dateRange !== prevProps.dateRange) {
            this.refreshComponent(this.props);
        }
    }

    refreshComponent(requestData) {
        let fromDate = requestData.dateRange[0].toISOString().split('T')[0];
        let toDate = requestData.dateRange[1].toISOString().split('T')[0];
        let dateFrom = convertLocalTimeToUTCString(fromDate, "00:00:00");
        let dateTo = convertLocalTimeToUTCString(toDate, "23:59:59");
        
        this.props.dataSource.GetRequest("/iot-service/v1/" + this.props.tenant + "/history/" + this.props.device.id + "?from=" + dateFrom + "&to=" + dateTo,
            data => {
                let series = [];
                let voltData = [];
                let tempData = [];
                let humidityData = [];
                let comments = {};
                let isHumidity = data.humidity[0] !== 255;

                for (let i = 0; i < data.sensor_time.length; i++) {
                    let time_series = new Date(data.sensor_time[i]);
                    let diff = time_series.getTimezoneOffset() * 60 * 1000;
                    let timestamp = time_series.getTime() - diff;
                    let volt_series = data.voltage[i];
                    let temp_series = data.temperature[i];
                    let humidity_series = isHumidity ? data.humidity[i] : null;

                    voltData.push([timestamp, volt_series]);
                    tempData.push([timestamp, temp_series]);
                    if (isHumidity) humidityData.push([timestamp, humidity_series]);

                    // Check for tolerance breaches
                    if (volt_series > this.state.tolerance.voltage) {
                        comments.voltage = comments.voltage || [];
                        comments.voltage.push({ timestamp, value: volt_series });
                    }
                    if (temp_series > this.state.tolerance.temperature) {
                        comments.temperature = comments.temperature || [];
                        comments.temperature.push({ timestamp, value: temp_series });
                    }
                    if (isHumidity && humidity_series > this.state.tolerance.humidity) {
                        comments.humidity = comments.humidity || [];
                        comments.humidity.push({ timestamp, value: humidity_series });
                    }
                }

                series.push({ name: "Voltage", data: voltData });
                series.push({ name: "Temperature", data: tempData });
                if (isHumidity) {
                    series.push({ name: "Humidity", data: humidityData });
                }

                this.setState({ series, comments, loaded: true });
            });
    }

    renderComments() {
        return (
            <div className="comments-container">
                {Object.entries(this.state.comments).map(([key, points]) => (
                    <div key={key}>
                        <h4>{key.charAt(0).toUpperCase() + key.slice(1)} Exceeding Tolerance</h4>
                        {points.map((point, index) => (
                            <div key={index}>
                                <span>{`Timestamp: ${new Date(point.timestamp).toLocaleString()} - Value: ${point.value}`}</span>
                                <input
                                    type="text"
                                    placeholder="Add a comment"
                                    onChange={(e) => this.handleCommentChange(key, point.timestamp, e.target.value)}
                                />
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        );
    }

    handleCommentChange(key, timestamp, value) {
        this.setState((prevState) => {
            const newComments = { ...prevState.comments };
            newComments[key] = newComments[key] || [];

            const pointIndex = newComments[key].findIndex(point => point.timestamp === timestamp);
            if (pointIndex !== -1) {
                newComments[key][pointIndex].comment = value; // Update comment
            } else {
                newComments[key].push({ timestamp, comment: value }); // Add new comment
            }

            return { comments: newComments };
        });
    }
}

export default HistoryChart;
