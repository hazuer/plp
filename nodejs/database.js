const mysql = require('mysql')

/**
 * Database class both individual connections and a pool of connections
 * Use debug:true to deploy sql and result | debug:false to production
 */
module.exports = class Database {
  constructor (debug = false) {
    this.debug = debug
    this.dbConfig = {
      host: 'srv1441.hstgr.io',
      user: 'u369447447_sysadminbd',
      password: 'b9@RlsPuso8ofA',
      port: 3306,
      database: 'u369447447_plp'
    }
    this.pool = mysql.createPool(this.dbConfig)
  }

  createDBConnection () {
    return mysql.createConnection(this.dbConfig)
  }

  checkDBConnection (dbConnection) {
    return new Promise((resolve, reject) => {
      dbConnection.connect((err) => {
        if (err) {
          console.error('Error connecting to MySQL:', err.message)
          reject(err)
        } else {
          console.log('Connected to MySQL Database')
          resolve()
        }
      })
    })
  }

  endDBConnection (dbConnection) {
    dbConnection.end((err) => {
      if (err) {
        console.error('Error closing MySQL connection:', err.message)
      } else {
        console.log('MySQL connection closed')
      }
    })
  }

  checkDBPool () {
    return new Promise((resolve, reject) => {
      this.pool.getConnection((err, connection) => {
        if (err) {
          console.error('Error getting connection from MySQL pool:', err.message)
          reject(err)
        } else {
          console.log('MySQL Pool Connection Established')
          connection.release()
          resolve(this.pool)
        }
      })
    })
  }

  endDBPool () {
    this.pool.end((err) => {
      if (err) {
        console.error('Error closing MySQL pool:', err.message)
      } else {
        console.log('MySQL Pool Connection Closed')
      }
    })
  }

  processDBQuery (sql, connection, values) {
    return new Promise((resolve, reject) => {
      connection.query(sql, values, (err, result) => {
        if (err) {
          console.error('Error executing MySQL query:', err.message)
          reject(err)
        } else {
          resolve(result)
        }
      })
    })
  }

  /**
 *
 * @param {string} sql - SQL sentence
 * @param {*} values
 * @returns {Object} - Result of the executed query
 */
  processDBQueryUsingPool (sql, values) {
    return new Promise((resolve, reject) => {
      this.pool.getConnection((err, connection) => {
        if (err) {
          console.error('Error getting connection from MySQL pool:', err.message)
          reject(err)
        } else {
          connection.query(sql, values, (err, result) => {
            connection.release()
            if (err) {
              console.error('Error executing MySQL query from pool:', err.message)
              if (this.debug === 'true') {
                console.log('###Error###:', sql, result)
              }
              reject(err)
            } else {
              if (this.debug === 'true') {
                console.log(':::Success:::', sql, result)
                console.log('================================================')
              }
              resolve(result)
            }
          })
        }
      })
    })
  }
}
