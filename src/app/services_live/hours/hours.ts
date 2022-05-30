import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class HoursProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    viewAttendanceLogs(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance', postParams, headers)
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

    viewAttendancePending(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
            'status' : 'pending'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance', postParams, headers)
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

    viewAttendanceReject(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'page' : page,
            'limit' : '10',
            'status' : 'reject'
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance', postParams, headers)
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

    getApproversNameAndStatus(timeinID: any, lastSource: any, status: any, fileDate: any, source: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_time_in_id' : timeinID,
            'last_source' : lastSource,
            'time_in_status' : status,
            'change_log_date_filed' : fileDate,
            'source' : source
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/emp_time_in/get_approvers_name_and_status', postParams, headers)
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

    checkLocks() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/attendance/check_locks', {}, {})
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

    addLogs(logDetails: any, split_id: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'new_employee_timein_date' : logDetails.schedDate,
            'employee_timein_date' : logDetails.timeInDate,
            'time_in' : logDetails.timeIn,
            'time_out' : logDetails.timeOut,
            'lunch_in' : logDetails.lunchIn,
            'lunch_out' : logDetails.lunchOut,
            'first_break_in' : logDetails.firstBreakIn,
            'first_break_out' : logDetails.firstBreakOut,
            'second_break_in' : logDetails.secondBreakIn,
            'second_break_out' : logDetails.secondBreakOut,
            'schedule_blocks_id' : split_id,
            'reason' : logDetails.reason,
            'flag_halfday' : logDetails.halfday,
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/add_logs', postParams, headers)
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

    splitChecker(date: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'datetime' : date,
            'check_split' : "1",
            'is_change_log' : "false"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/check_split', postParams, headers)
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

    changeLogs(emp_timein_id: any, logDetails: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_timein' : emp_timein_id,
            'emp_schedule_date' : logDetails.schedDate,
            'time_in' : logDetails.timeIn,
            'time_out' : logDetails.timeOut,
            'lunch_in' : logDetails.lunchIn,
            'lunch_out' : logDetails.lunchOut,
            'first_break_in' : logDetails.firstBreakIn,
            'first_break_out' : logDetails.firstBreakOut,
            'second_break_in' : logDetails.secondBreakIn,
            'second_break_out' : logDetails.secondBreakOut,
            'reason' : logDetails.reason,
            'flag_halfday' : logDetails.halfday,
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/change_log', postParams, headers)
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

    checkTotalHours(logDetails: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'employee_timein' : logDetails.empTimeinId,
            'emp_schedule_date' : logDetails.schedDate,
            'time_in' : logDetails.timeIn,
            'time_out' : logDetails.timeOut,
            'lunch_in' : logDetails.lunchIn,
            'lunch_out' : logDetails.lunchOut,
            'first_break_in' : logDetails.firstBreakIn,
            'first_break_out' : logDetails.firstBreakOut,
            'second_break_in' : logDetails.secondBreakIn,
            'second_break_out' : logDetails.secondBreakOut,
            'reason' : logDetails.reason,
            'flag_halfday' : logDetails.halfday,
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/check_total_hours', postParams, headers)
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

    breakChecker(changeDate: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'datetime' : changeDate,
            'get_breaks' : "1"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/check_breaks', postParams, headers)
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

    checkPayrollLock(date: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'start_date' : date
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/if_payroll_is_locked', postParams, headers)
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

    overtimeLogValidation(logs: any) {
        if (logs) {

            let er_msg: any = [];
            if (logs.startDate > logs.endDate) {
                er_msg.push('End Date should not be less than the start date.');
            }

            if (er_msg.length > 0) {
                let msg_er: string = '<ul>';
                for (let msg of er_msg) {
                    msg_er += '<li>' + msg +'</li>';
                }
                msg_er += '</ul>';

                return msg_er;
            } else {
                return false;
            }
        }

        return "Invalid log entries.";
    }

    logsValidation(logs: any) {
        if (logs) {
            
            let er_msg: any = [];
            if (logs.timeIn > logs.timeOut) {
                er_msg.push('Time out should not be less than the time in');
            }

            if (logs.lunchOut) {
                if ((logs.timeIn > logs.lunchOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }                    
                }
            }

            if (logs.lunchIn) {
                if ((logs.timeIn > logs.lunchIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }

            if (logs.firstBreakIn) {
                if ((logs.timeIn > logs.firstBreakIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }
            if (logs.firstBreakOut) {
                if ((logs.timeIn > logs.firstBreakOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in.');
                    }   
                }
            }
            if (logs.secondBreakIn) {
                if ((logs.timeIn > logs.secondBreakIn)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in2.');
                    }   
                }
            }
            if (logs.secondBreakOut) {
                if ((logs.timeIn > logs.secondBreakOut)) {
                    if (er_msg.indexOf('Breaks should not be less than the time in.') == -1) {
                        er_msg.push('Breaks should not be less than the time in1.');
                    }   
                }
            }

            //lunch
            if (logs.lunchOut > logs.lunchIn) {
                er_msg.push('Lunch in should not be less then than lunch out');
            }

            if ((logs.firstBreakOut > logs.firstBreakIn) || (logs.secondBreakOut > logs.secondBreakIn)) {
                er_msg.push('Break in should not be less than the break out');
            }

            if ((logs.timeOut < logs.lunchOut) || (logs.timeOut < logs.lunchIn)
            || (logs.timeOut < logs.firstBreakIn) || (logs.timeOut < logs.firstBreakOut)
            || (logs.timeOut < logs.secondBreakIn) || (logs.timeOut < logs.secondBreakOut)) {
                er_msg.push('Time out should not be less than the breaks.');
            }

            if (er_msg.length > 0) {
                let msg_er: string = '<ul>';
                for (let msg of er_msg) {
                    msg_er += '<li>' + msg +'</li>';
                }
                msg_er += '</ul>';

                return msg_er;
            } else {
                return false;
            }
        }
        
        return "Invalid log entries.";
    }
    
    clockIn(location: any, empId: any, accId: any, compId: any, psaId: any, cloudId: any, coorLat: any, coorLong: any) {
        
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'location' : location,
            'emp_id' : empId,
            'account_id' : accId,
            'company_id' : compId,
            'psa_id' : psaId,
            'cloud_id' : cloudId,
            'longitude' : coorLong,
            'latitude' : coorLat
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/attendance/clock_in_v2', postParams, headers)
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

    clockGuardSettings2(session: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'emp_id' : session.empid,
            'account_id' : session.accountid,
            'company_id' : session.compId,
            'psa_id' : session.psa_id,
            'cloud_id' : session.cloudid
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/dashboard/get_clockin_settings', postParams, headers)
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

    clockGuardSettings() {
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/dashboard/get_clockin_settings', {}, {})
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

    getAllOvertime(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'status' : '',
            'page' : page,
            'limit' : "10"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime', postParams, headers)
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

    getApprovedOvertime(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'status' : 'approved',
            'page' : page,
            'limit' : "10"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime', postParams, headers)
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

    getPendingOvertime(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'status' : 'pending',
            'page' : page,
            'limit' : "10"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime', postParams, headers)
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

    /** Rejected Overtime **/
    getRejectedOvertime(page: string) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'status' : 'reject',
            'page' : page,
            'limit' : "10"
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime', postParams, headers)
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

    getOvertimeApprovers(overtimeId: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'overtime_id' : overtimeId
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime/get_approvers', postParams, headers)
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

    calculateOvertimeRequest(logDetails: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'start_date' : logDetails.startDate,
            'end_date' : logDetails.endDate
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime/calculate_overtime', postParams, headers)
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

    checkPayrollLockOt(startDate: any) {
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'start_date' : startDate
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime/if_payroll_is_locked', postParams, headers)
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

    submitOvertime(logs: any) {
        console.log("inside service");
        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'start_date' : logs.startDate,
            'end_date' : logs.endDate,
            'purpose' : logs.reason,
        };

        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/overtime/apply_overtime', postParams, headers)
            .then(data => {
                console.log(data.status);
                console.log(data.data); // data received by server
                console.log(data.headers);
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                console.log(error.status);
                console.log(error.error); // error message as string
                console.log(error.headers);
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
    }
}
