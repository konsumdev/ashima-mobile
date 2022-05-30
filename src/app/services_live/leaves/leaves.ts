import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class LeavesProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getBalance() {

        let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};

        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/leave/get_leave_type', {}, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
            })
            .catch(error => {                
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    getLeaveHistory(page: string, type: any) {

        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
            'lv_status' : type,
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/leave', postParams, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
                })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    checkWorkSchedule(startDate: any, endDate: any, ifPartial: any, leaveType: any, flexiHrs: any) {

        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'end_date' : endDate,
            'flexi_hrs' : flexiHrs,
            'if_partial' : ifPartial,
            'leave_type' : leaveType,
            'start_date' : startDate,
            'check_work_schedule' : "1",
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/leave/get_work_schedule', postParams, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
                })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });

    }

    checkPayrollLock(startDate: any) {

        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'shift_date' : startDate,
            'if_payroll_is_locked' : "1",
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/leave/if_payroll_is_locked', postParams, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
                })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    getTotalLeaveRequest(startDate: any, startTime: any, endDate: any, endTime: any, flexiHrs: any,
        ifNs: any, ifPartial: any, leaveType: any, lunchRequired: any) 
    {
        var ns = (ifNs) ? 'yes' : 'no';
        var partial = (ifPartial) ? 'yes' : 'no';

        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'end_date' : endDate,
            'end_time' : endTime,
            'flexi_hrs' : flexiHrs,
            'if_NS' : ns,
            'if_partial' : partial,
            'leave_type' : leaveType,
            'lunch_hr_required' : lunchRequired,
            'start_date' : startDate,
            'start_time' : startTime,
            'tlr' : "1",
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/leave/get_total_leaves', postParams, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
                })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }

    submitLeave(startDate: any, startTime: any, endDate: any, endTime: any, ifPartial: any, 
        leaveType: any, lunchRequired: any, leaveRequest: any, prevCredits: any,
        reason: any, reqDoc: any, uploadNumbers: any) {

            var partial = (ifPartial) ? 'Partial Day' : '';
            
            let headers = {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                "Access-Control-Allow-Credentials" : "true",
            };
    
            let postParams = { 
                'cont_tlr_hidden' : leaveRequest,
                'end_date' : endDate,
                'end_time' : endTime,
                'leave_request_type' : partial,
                'leave_type' : leaveType,
                'lunch_hr_required' : lunchRequired,
                'start_date' : startDate,
                'start_time' : startTime,
                'reason' : reason,
                'required_doc' : '',
                'upload_numbers' : uploadNumbers,
                'tlr' : "1",
            };
            
            return new Promise((resolve, reject) => {
                this.httpr.post(this.apiUrl + '/api/leave/submit_leaves', postParams, headers)
                .then(data => {
                    console.log("inside provider");
                    console.log(data);
                        if (data.data) {
                            let a = JSON.parse(data.data);
                            resolve(a);
                        }
                    })
                .catch(error => {
                    if (error.error) {
                        let e = JSON.parse(error.error);
                        reject(e);
                    }
                });
            });
            
    }
}
