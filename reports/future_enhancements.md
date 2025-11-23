# Future Enhancements

This document outlines potential future enhancements for the ML recommendation system.

## Very High Priority

*   **Real-Time Updates:** Implement WebSockets to provide real-time updates to the apron view. This would eliminate the need for the current 5-second polling and would provide a much better user experience.
*   **Automated Retraining:** Set up a scheduled task (cron job) to automatically retrain the model on a regular basis (e.g., weekly or monthly). This would ensure that the model is always up-to-date with the latest data.

## High Priority

*   **Dynamic Time-based Features**: Incorporate time-based features that are calculated dynamically at prediction time, such as time until next scheduled departure from a stand, to improve the model's awareness of the current operational tempo.
*   **Advanced Models:** Experiment with more advanced models, such as Gradient Boosting (e.g., XGBoost, LightGBM) or a simple Neural Network, to see if they can improve prediction accuracy.

## Medium Priority

*   **More Features:** Incorporate additional features into the model, such as:
    *   Time of day (e.g., morning, afternoon, evening)
    *   Day of the week
    *   Seasonality (e.g., holidays, peak travel seasons)
    *   Aircraft weight/size category for more granular assignments.
*   **Explainable AI (XAI)**: Integrate a library like SHAP or LIME to provide explanations for why the model recommended a particular stand. This would increase user trust and provide more insight into the model's decision-making process.

## Low Priority

*   **A/B Testing:** Implement a framework for A/B testing different models in a live environment. This would allow for data-driven decisions about which model performs best.
*   **Batch Predictions:** If the system experiences high load, consider implementing batch predictions to improve throughput.
*   **Simulation Mode**: Create a simulation mode where users can test the impact of different "what-if" scenarios, such as a runway closure or a sudden influx of flights, on stand availability and recommendations.

## Dependencies

*   **Real-Time Updates (WebSockets):** Requires a WebSocket server (e.g., Ratchet for PHP or a separate Node.js server) and a client-side JavaScript implementation.
*   **Automated Retraining:** Requires access to the server's task scheduler (e.g., cron on Linux or Task Scheduler on Windows).
*   **Dynamic Time-based Features:** Requires access to real-time flight schedule data, potentially from an external API or another database table.
*   **Advanced Models (XGBoost, etc.):** Requires installation of the respective Python libraries (e.g., `pip install xgboost`).
*   **Explainable AI (XAI):** Requires installation of SHAP or LIME Python libraries (`pip install shap`).

## Data Needs for Advanced Models

*   **Time-based Features:** To implement time-based features, the `aircraft_movements` table would need to be augmented with scheduled and estimated arrival/departure times, in addition to the actual on/off block times.
*   **Aircraft Weight/Size:** To incorporate aircraft weight/size, a new table or a new column in the `aircraft_details` table would be needed to store this information. This data would need to be sourced from an external provider or entered manually.
*   **Seasonality:** To model seasonality, the system would need access to a calendar of holidays and peak travel seasons.

## Discussions and Assumptions

*   **User Adoption:** The success of this feature depends on user adoption. If users find the recommendations to be inaccurate or not useful, they will likely stop using the feature. It is important to gather user feedback and continuously improve the model.
*   **Data Quality:** The model is only as good as the data it is trained on. It is important to ensure that the data in the `aircraft_movements` table is accurate and complete.
*   **Scalability:** The current implementation with `shell_exec` may not scale well if the number of prediction requests increases significantly. A more robust solution, such as a dedicated microservice for predictions, may be needed in the future.
