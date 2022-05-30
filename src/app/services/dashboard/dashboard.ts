import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class DashboardProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getTimeinLogs() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/clockin/get_latest_time_in', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    dashTimesheets() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/timesheet', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    dashMissedPunches() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/getMissedPunches', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    dashAttendance() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/attendance', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    nextShift() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/next_shift', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getPayslip() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/getPayslip', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    nextPayDate() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/next_pay_date', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    leaveDoughnut() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/dashboard/leave_doughnut', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    missedPunchList() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/timesheet/missed', {withCredentials: true}).subscribe(
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
