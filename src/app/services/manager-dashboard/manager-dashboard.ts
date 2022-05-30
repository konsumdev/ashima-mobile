import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ManagerDashboardProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getHeadCount() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/employee_head_count', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getEmployeesClockedIn() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/employees_clocked_in', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getNoShow() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/employees_no_show', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getOutOnLeave() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/employees_out_on_leave', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getSched() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/get_schedule', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getHolidays() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/holidays', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getTardyList() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/tardiness_count', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getEarlyBirds() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/get_early_birds', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    todos() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/todo_leave', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    missingTimesheets() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/missing_employees_timesheet_count', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    missedPunches() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/team_missed_punches', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    birthdays() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/birthdays', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    anniversaries() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/managerDashboard/anniversaries', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }
}
